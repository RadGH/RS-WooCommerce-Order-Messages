<?php

if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Determines if a message should appear in the given location/type based on the order status
 *
 * @param $message
 * @param $location
 * @param $type
 * @param $order
 *
 * @return bool
 */
function __wom_should_show_message_here( $message, $location, $type, $order ) {
	if ( !($order instanceof WC_Order) ) return false;
	
	// Location match
	// Valid locations: above-details, below-details, bottom
	if ( $message['location'] != $location ) return false;
	
	// Get order status, allow filtering
	$order_status = apply_filters( 'rs_wom_override-order-status', $order->get_status(), $order );
	
	// Status match
	// Example: completed, cancelled, on-hold
	if ( !in_array( $order_status, $message['statuses'] ) ) return false;
	
	// Type match
	// Valid types: email, website
	if ( !in_array( $type, $message['type'] ) ) return false;
	
	return true;
}

/**
 * Designed for use with usort().
 * Sort an array (of messages) by their menu_order key.
 *
 * @param $a
 * @param $b
 *
 * @return mixed
 */
function __wom_sort_array_by_menu_order( $a, $b ) {
	return $a['menu_order'] - $b['menu_order'];
}


/**
 * Display custom order and receipt messages, configurable per-product or at a global level.
 * For website, See woocommerce/checkout/thankyou.php
 * For email, uses built-in hooks
 *
 * @param $order
 * @param $location
 * @param $type
 *
 * @return void
 */
function __wom_do_order_message( $order, $location, $type ) {
	if ( is_numeric($order) ) $order = new WC_Order($order);
	if ( !($order instanceof WC_Order) ) return;
	
	$messages_to_display = array();
	
	// Get global messages.
	$global_messages = get_field('wom_order_messages', 'options');
	if ( $global_messages ) foreach( $global_messages as $i => $m ) {
		// Check if the message should be shown in this spot.
		if ( !empty($m['content']) && __wom_should_show_message_here( $m, $location, $type, $order ) ) {
			$messages_to_display[ 'global-' . $i ] = $m;
		}
	}
	
	// Get messages for each product.
	foreach( $order->get_items() as $i => $order_item ) {
		if ( !($order_item instanceof WC_Order_Item) ) continue;
		
		// Get all messages for this product
		$messages = get_field( 'wom_order_messages', $order_item->get_product_id() );
		
		// Loop through each custom message
		if ( $messages ) foreach( $messages as $m ) {
			// Check if the message should be shown in this spot.
			if ( !empty($m['content']) && __wom_should_show_message_here( $m, $location, $type, $order ) ) {
				// Yes!
				// This message should be displayed. Add to an array for now, we'll sort that later.
				// Use the array key to uniquely identify the message and prevent duplicate messages if the user orders two of the same product (eg, shirt size small and medium).
				$messages_to_display[ 'product-' . $order_item->get_product_id() . '-' . $location .'-' . $type .'-' . $i ] = $m;
			}
		}
	}
	
	// No messages to display? Abort
	if ( !$messages_to_display ) return;
	
	// Sort messages by priority
	usort( $messages_to_display, '__wom_sort_array_by_menu_order' );
	
	// Put messages in this location into a container div for styling
	echo sprintf( '<div class="wom-custom-order-messages location-%s type-%s">', esc_attr( $location ), esc_attr( $type ) );
	
	// Do the messages
	foreach( $messages_to_display as $i => $m ) {
		// Allow filtering the raw message
		$content = apply_filters( 'wom-custom-order-message', $m['content'], $order, $location, $type );
		
		// Expand shortcodes
		$content = do_shortcode($content);
		
		if ( $content ) {
			// Build a div for this message item, with useful classes and a unique ID
			echo sprintf(
				'<div id="order-%d-%d-%s-%s" class="wom-message-item location-%s type-%s">',
				
				// id -- example: order-400-25-bottom-website
				esc_attr( $order->get_id() ),
				esc_attr( $order_item->get_product_id() ),
				esc_attr( $location ),
				esc_attr( $type ),
				
				// class
				esc_attr( $location ), // above-details
				esc_attr( $type ) // website
			);
			
			// Put the content
			echo $content;
			
			// End the message item
			echo '</div>' . "\n";
		}
	}
	
	// End container
	echo '</div>';
}

// Website aliases
function wom_insert_order_messages_above_details( $order ) {
	__wom_do_order_message( $order, 'above-details', 'website' );
}
function wom_insert_order_messages_below_details( $order ) {
	__wom_do_order_message( $order, 'below-details', 'website' );
}
function wom_insert_order_messages_bottom( $order ) {
	__wom_do_order_message( $order, 'bottom', 'website' );
}

// Email aliases
function wom_insert_order_messages_above_details_email( $order ) {
	__wom_do_order_message( $order, 'above-details', 'email' );
}
function wom_insert_order_messages_below_details_email( $order ) {
	__wom_do_order_message( $order, 'below-details', 'email' );
}
function wom_insert_order_messages_bottom_email( $order ) {
	__wom_do_order_message( $order, 'bottom', 'email' );
}

// Website hooks
add_action( 'woocommerce_order_details_before_order_table', 'wom_insert_order_messages_above_details', 5 );
add_action( 'woocommerce_order_details_after_order_table', 'wom_insert_order_messages_below_details', 200 );
add_action( 'woocommerce_order_details_after_order_table', 'wom_insert_order_messages_bottom', 400 ); // Same as below-details but that's the only hook here.

// Email hooks
add_action( 'woocommerce_email_order_details', 'wom_insert_order_messages_above_details_email', 2 );
add_action( 'woocommerce_email_after_order_table', 'wom_insert_order_messages_below_details_email', 200 );
add_action( 'woocommerce_email_customer_details', 'wom_insert_order_messages_bottom_email', 200 );