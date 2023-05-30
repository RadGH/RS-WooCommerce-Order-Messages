<?php
/*
Plugin Name: RS WooCommerce Order Messages
Version:     1.0.1
Plugin URI:  http://radleysustaire.com/
Description: Adds configurable messages that can be displayed on the checkout page or emails in WooCommerce. Messages can be configured to appear in various locations, and can be set to only appear for certain order statuses. Messages can be configured at a global or product level.
Author:      Radley Sustaire
Author URI:  mailto:radleygh@gmail.com
License:     GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
*/

if ( !defined( 'ABSPATH' ) ) exit;

define( 'AA_WOM_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define( 'AA_WOM_PATH', dirname(__FILE__) );
define( 'AA_WOM_VERSION', '1.0.1' );

add_action( 'plugins_loaded', 'aa_wom_init_plugin' );

// Initialize plugin: Load plugin files
function aa_wom_init_plugin() {
	if ( !function_exists('WC') ) {
		add_action( 'admin_notices', 'aa_wom_warn_no_woocommerce' );
		return;
	}
	
	if ( !function_exists('acf') ) {
		add_action( 'admin_notices', 'aa_wom_warn_no_acf' );
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
	
	include_once( AA_WOM_PATH . '/fields/product-order-messages.php' );
	include_once( AA_WOM_PATH . '/includes/messages.php' );
}

// Display a warning when WooCommerce is not active
function aa_wom_warn_no_woocommerce() {
	?>
	<div class="error">
		<p><strong>A+A WooCommerce Order Messages:</strong> This plugin requires WooCommerce in order to operate. Please install and activate WooCommerce, or disable this plugin.</p>
	</div>
	<?php
}
// Display a warning when WooCommerce is not active
function aa_wom_warn_no_acf() {
	?>
	<div class="error">
		<p><strong>A+A WooCommerce Order Messages:</strong> This plugin requires Advanced Custom Fields Pro in order to operate. Please install and activate ACF Pro, or disable this plugin.</p>
	</div>
	<?php
}