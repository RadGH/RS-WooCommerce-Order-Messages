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
function wom_should_display_at_location( $message, $location, $type, $order ) {
	if ( !($order instanceof WC_Order) ) return false;
	
	// Message content must not be blank
	if ( empty($message['content']) ) return false;
	
	// Location match
	// Valid locations: above-details, below-details, bottom
	if ( $message['location'] != $location ) return false;
	
	// Status match
	// Example: completed, cancelled, on-hold
	if ( !in_array( $order->get_status(), $message['statuses'] ) ) return false;
	
	// Type match
	// Valid types: email, website
	if ( !in_array( $type, $message['type'] ) ) return false;
	
	return true;
}

/**
 * Designed for use with usort().
 * Sort an array (of messages) by their menu_order key.
 *
 * @param string|int $a
 * @param string|int $b
 *
 * @return int
 */
function wom_sort_menu_order( $a, $b ) {
	return (int) $a['menu_order'] - (int) $b['menu_order'];
}

/**
 * Removes duplicate messages
 *
 * @param array[] $messages {
 *     @type string   $location    "above-details"
 *     @type string[] $statuses    array( 'pending', 'processing', ... )
 *     @type string   $type        array( 'website', 'email' )
 *     @type string   $menu_order  "0"
 *     @type string   $content     "This message will be added to the email/thank you page"
 * }
 *
 * @return array[]
 */
function wom_remove_duplicate_messages( $messages ) {
	$unique_messages = array();
	
	// Loop through each message
	foreach( $messages as $i => $m ) {
		
		// Clean the message before comparison
		$content = $m['content'];
		$content = wp_strip_all_tags( $content );
		$content = trim( $content );
		
		// Check if this message has already been added
		if ( in_array( $content, $unique_messages, true ) ) {
			// Remove duplicate messages
			unset( $messages[$i] );
		}else{
			// Keep new messages
			$unique_messages[] = $content;
		}
		
	}
	
	return $messages;
}


/**
 * Display custom order and receipt messages, configurable per-product or at a global level.
 * For website, See woocommerce/checkout/thankyou.php
 * For email, uses built-in hooks
 *
 * @param int|WC_Order $order
 * @param string $location
 * @param string $type
 *
 * @return void
 */
function wom_display_order_message( $order, $location, $type ) {
	$order = wc_get_order($order);
	if ( ! $order instanceof WC_Order ) return;
	
	// Add messages displayed at this location to an array
	$messages = array();
	
	// Get global messages.
	$global_messages = get_field('wom_order_messages', 'options');
	
	if ( $global_messages ) foreach( $global_messages as $msg ) {
		
		// Check if the message should be shown in this spot.
		if ( wom_should_display_at_location( $msg, $location, $type, $order ) ) {
			
			$messages[] = $msg;
			
		}
		
	}
	
	// Get messages for each product in the order
	if ( $order->get_items() ) foreach( $order->get_items() as $order_item ) {
		if ( ! $order_item instanceof WC_Order_Item_Product ) continue;
		
		// Get the product ID
		$product_id = $order_item->get_product_id();
		
		// Get messages for this product
		$product_messages = get_field( 'wom_order_messages', $product_id );
		
		// Loop through each custom message
		if ( $product_messages ) foreach( $product_messages as $msg ) {
			
			// Check if the message should be shown here.
			if ( wom_should_display_at_location( $msg, $location, $type, $order ) ) {
				
				$messages[] = $msg;
				
			}
			
		}
	}
	
	// For handling more than 1 message
	if ( count( $messages ) > 1 ) {
		
		// Sort remaining messages by their menu order
		usort( $messages, 'wom_sort_menu_order' );
		
		// Remove messages with duplicate content
		$messages = wom_remove_duplicate_messages( $messages );
	
	}
	
	// Allow filtering the messages in a plugin or functions.php
	$messages = apply_filters( 'wom/messages', $messages, $order, $location, $type );
	
	// Abort if no messages should be added to this location
	if ( ! $messages ) {
		return;
	}
	
	// Add a container for styling
	echo sprintf( '<div class="wom-custom-order-messages location-%s type-%s">', esc_attr( $location ), esc_attr( $type ) );
	
	// Display each message in its own div
	foreach( $messages as $msg ) {
		
		echo '<div class="wom-message-item">';
		
		echo do_shortcode($msg['content']);
		
		echo '</div>';
		
	}
	
	// Close the container
	echo '</div>';
}

// Website aliases
function wom_insert_order_messages_above_details( $order ) {
	wom_display_order_message( $order, 'above-details', 'website' );
}
function wom_insert_order_messages_below_details( $order ) {
	wom_display_order_message( $order, 'below-details', 'website' );
}
function wom_insert_order_messages_bottom( $order ) {
	wom_display_order_message( $order, 'bottom', 'website' );
}

// Email aliases
function wom_insert_order_messages_above_details_email( $order ) {
	wom_display_order_message( $order, 'above-details', 'email' );
}
function wom_insert_order_messages_below_details_email( $order ) {
	wom_display_order_message( $order, 'below-details', 'email' );
}
function wom_insert_order_messages_bottom_email( $order ) {
	wom_display_order_message( $order, 'bottom', 'email' );
}

// Website hooks
add_action( 'woocommerce_order_details_before_order_table', 'wom_insert_order_messages_above_details', 5 );
add_action( 'woocommerce_order_details_after_order_table', 'wom_insert_order_messages_below_details', 200 );
add_action( 'woocommerce_order_details_after_order_table', 'wom_insert_order_messages_bottom', 400 ); // Same as below-details but that's the only hook here.

// Email hooks
add_action( 'woocommerce_email_order_details', 'wom_insert_order_messages_above_details_email', 2 );
add_action( 'woocommerce_email_after_order_table', 'wom_insert_order_messages_below_details_email', 200 );
add_action( 'woocommerce_email_customer_details', 'wom_insert_order_messages_bottom_email', 200 );