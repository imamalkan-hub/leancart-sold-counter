<?php
/**
 * Plugin Name: LeanCart Sold Counter
 * Plugin URI:  https://schlafklinikfinder.com/
 * Description: Calculates product sales from completed orders and displays them as social proof on the product page. Encourages purchases and speeds up the buying decision.
 * Version:     1.0.0
 * Author:      LeanCart
 * Author URI:  https://leancart.dev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sold-counter
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEANCART_SOLD_COUNTER_DEMO_SITE' ) ) {
	define( 'LEANCART_SOLD_COUNTER_DEMO_SITE', 'https://schlafklinikfinder.com' );
}

require_once __DIR__ . '/includes/class-leancart-framework.php';

final class LeanCart_Sold_Counter {

	const SLUG = 'sold-counter';

	public static function init() {
		LeanCart_Framework::boot( array(
			'slug'    => self::SLUG,
			'name'    => 'LeanCart Sold Counter',
			'version' => '1.0.0',
			'file'    => __FILE__,
			'site'    => LEANCART_SOLD_COUNTER_DEMO_SITE,
		) );

		// WooCommerce may load after these plugins; defer the check to runtime.
		add_action( 'admin_notices', array( __CLASS__, 'wc_notice' ) );

		load_plugin_textdomain( 'sold-counter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_action( 'leancart_' . self::SLUG . '_settings_fields', array( __CLASS__, 'fields' ) );

		// WooCommerce may load after this plugin; hooks are registered once WC is ready.
		if ( LeanCart_Framework::is_wc() ) {
			self::hooks();
		} else {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'hooks' ) );
		}
	}

	public static function wc_notice() {
		if ( LeanCart_Framework::is_wc() ) { return; }
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( 'LeanCart Sold Counter' ) . '</strong>: ';
		esc_html_e( 'This plugin requires WooCommerce to be active.', 'sold-counter' );
		echo '</p></div>';
	}

	public static function fields() {
		LeanCart_Framework::field_text( 'label', __('Label', 'sold-counter'), __('Example: sold', 'sold-counter'), 'text' );
		LeanCart_Framework::field_checkbox( 'show_product', __('Show on product page', 'sold-counter'), __('Yes', 'sold-counter') );
	}


	public static function hooks() {
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'increment' ) );
		add_action( 'woocommerce_after_add_to_cart_form', array( __CLASS__, 'display' ) );
		add_shortcode( 'lc_sold', array( __CLASS__, 'shortcode' ) );
	}

	public static function increment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) { return; }
		foreach ( $order->get_items() as $item ) {
			$id = $item->get_product_id();
			if ( ! $id ) { continue; }
			$count = (int) get_post_meta( $id, '_lc_sold', true );
			update_post_meta( $id, '_lc_sold', $count + $item->get_quantity() );
		}
	}

	public static function display() {
		global $product;
		if ( 'yes' !== LeanCart_Framework::setting( 'show_product', 'yes' ) || ! $product ) { return; }
		echo '<p class="lc-sold" style="color:#646970;font-size:13px;margin:10px 0">';
		echo esc_html( sprintf( __( '%d %s', 'sold-counter' ), self::count( $product->get_id() ), LeanCart_Framework::setting( 'label', __( 'units sold', 'sold-counter' ) ) ) );
		echo '</p>';
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'lc_sold' );
		return esc_html( self::count( (int) $atts['id'] ) );
	}

	private static function count( $id ) {
		return (int) get_post_meta( $id, '_lc_sold', true );
	}


}

LeanCart_Sold_Counter::init();

