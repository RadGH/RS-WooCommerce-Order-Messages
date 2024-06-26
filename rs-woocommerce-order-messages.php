<?php
/*
Plugin Name: RS WooCommerce Order Messages
Version:     1.2.1
Plugin URI:  https://radleysustaire.com/
Description: This plugin allows you to add one or more messages to your products which are displayed on the order email and thank you page.
Author:      Radley Sustaire
Author URI:  https://radleysustaire.com/
License:     GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
GitHub Plugin URI: https://github.com/RadGH/RS-WooCommerce-Order-Messages
*/

if ( !defined( 'ABSPATH' ) ) exit;

define( 'WOM_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define( 'WOM_PATH', dirname(__FILE__) );
define( 'WOM_VERSION', '1.2.1' );

add_action( 'plugins_loaded', 'rs_wom_init_plugin' );

// Initialize plugin: Load plugin files
function rs_wom_init_plugin() {
	if ( !function_exists('WC') ) {
		add_action( 'admin_notices', 'rs_wom_warn_no_woocommerce' );
		return;
	}
	
	if ( !function_exists('acf') ) {
		add_action( 'admin_notices', 'rs_wom_warn_no_acf' );
		return;
	}
	
	if ( function_exists('acf_add_options_sub_page') ) {
		acf_add_options_sub_page(array(
			'page_title' 	=> 'Order Messages',
			'menu_title' 	=> 'Order Messages',
			'redirect' 		=> false,
			'menu_slug'     => 'wom-order-messages',
			'parent_slug'   => 'woocommerce'
		));
	}
	
	include_once( WOM_PATH . '/fields/product-order-messages.php' );
	include_once( WOM_PATH . '/includes/messages.php' );
}

// Display a warning when WooCommerce is not active
function rs_wom_warn_no_woocommerce() {
	?>
	<div class="error">
		<p><strong>RS WooCommerce Order Messages:</strong> This plugin requires WooCommerce in order to operate. Please install and activate WooCommerce, or disable this plugin.</p>
	</div>
	<?php
}
// Display a warning when WooCommerce is not active
function rs_wom_warn_no_acf() {
	?>
	<div class="error">
		<p><strong>RS WooCommerce Order Messages:</strong> This plugin requires Advanced Custom Fields Pro in order to operate. Please install and activate ACF Pro, or disable this plugin.</p>
	</div>
	<?php
}