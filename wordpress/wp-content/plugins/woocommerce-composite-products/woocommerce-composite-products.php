<?php
/*
 * Plugin Name: WooCommerce Composite Products
 * Plugin URI: http://woothemes.com/woocommerce
 * Description: Woocommerce extension for creating composite products, based on existing items.
 * Author: SomewhereWarm
 * Author URI: http://www.somewherewarm.net/
 * Version: 1.5.3
 * Text Domain: woocommerce-bto
 * Domain Path: /languages/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '0343e0115bbcb97ccd98442b8326a0af', '216836' );

// Check if WooCommerce is active
if ( ! is_woocommerce_active() )
	return;

/**
 * Main Composite (BTO) Products Extension Class
 *
 * Contains the main functions for enabling Composite Products
 *
 * @class WC_BTO
 * @author SomewhereWarm
 */

class WC_BTO {

	var $version = '1.5.3';

	var $addons_prefix = '';
	var $nyp_prefix 	= '';

	var $show_product_filter_params = array();

	public function __construct() {

		add_action( 'plugins_loaded', array($this, 'woo_bto_plugins_loaded') );
		add_action( 'init', array($this, 'woo_bto_init') );

	}

	function woo_bto_plugin_url() {
		return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}

	function woo_bto_plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	function woo_bto_plugins_loaded() {

		global $woocommerce;

		// WC 2 check
		if ( version_compare( $woocommerce->version, '2.0.11' ) <= 0 ) {
			add_action( 'admin_notices', array( $this, 'woo_bto_admin_notice' ) );
			return false;
		}

		add_action( 'wp_ajax_woo_bto_show_product', array( $this, 'woo_bto_show_product' ) );
		add_action( 'wp_ajax_nopriv_woo_bto_show_product', array( $this, 'woo_bto_show_product' ) );

		include( 'classes/class-wc-product-bto.php' );

		// Admin jquery
		add_action( 'admin_enqueue_scripts', array( $this, 'woo_bto_admin_scripts' ) );
		// Front end variation select box jquery for multiple variable products
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_bto_frontend_scripts' ) );

		// Creates the admin panel tab
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'woo_bto_product_write_panel_tab' ) );

		// Creates the panel for selecting product options
		add_action( 'woocommerce_product_write_panels', array( $this, 'woo_bto_product_write_panel' ) );

		add_filter( 'product_type_options', array( $this, 'woo_bto_type_options' ) );

		// Processes and saves the necessary post metas from the selections made above
		add_action( 'woocommerce_process_product_meta_bto', array( $this, 'woo_bto_process_bundle_meta' ) );

		// Allows the selection of the 'composite product' type
		add_filter( 'product_type_selector', array( $this, 'woo_bto_product_selector_filter' ) );

		// Single product template
		add_action( 'woocommerce_bto_add_to_cart', array( $this, 'woo_bto_add_to_cart' ) );

		// Filter add_to_cart_url and add_to_cart_text when product type is 'composite'
		add_action( 'add_to_cart_url', array( $this, 'woo_bto_add_to_cart_url' ), 10 );
		add_action( 'add_to_cart_text', array( $this, 'woo_bto_add_to_cart_text' ), 10 );

		// Validate composite add-to-cart
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'woo_bto_validation' ), 10, 6 );

		// Add composite configuration data to all composited items
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'woo_bto_add_cart_item_data' ), 10, 2 );

		// Add composited items to the cart
		add_action( 'woocommerce_add_to_cart', array( $this, 'woo_bto_add_items_to_cart' ), 10, 6 );

		// Modify cart item data for composite products
		add_filter( 'woocommerce_add_cart_item', array( $this, 'woo_bto_add_cart_item_filter' ), 10, 2 );

		// Preserve data in cart
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'woo_bto_get_cart_data_from_session' ), 10, 2 );

		// Sync quantities of bundled items with bundle quantity
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'woo_bto_update_quantity_in_cart' ), 1, 2 );
		add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'woo_bto_update_quantity_in_cart' ) );

		// Control modification of composited items' quantity
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'woo_bto_cart_item_quantity' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'woo_bto_cart_item_remove_link' ), 10, 2 );

		// Filter price output shown in cart, review-order & order-details templates
		add_action( 'woocommerce_order_formatted_line_subtotal', array( $this, 'woo_bto_order_item_subtotal' ), 10, 3 );

		if ( version_compare( $woocommerce->version, '2.1.0' ) >= 0 ) {
			add_filter( 'woocommerce_cart_item_price', array( $this, 'woo_bto_cart_item_price' ), 10, 3 );
		} else {
			add_filter( 'woocommerce_cart_item_price_html', array( $this, 'woo_bto_cart_item_price' ), 10, 3 );
		}

		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'woo_bto_item_subtotal' ), 10, 3 );
		add_filter( 'woocommerce_checkout_item_subtotal', array( $this, 'woo_bto_item_subtotal' ), 10, 3 );

		//add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'woo_bto_restore_totals' ), 100 );

		// Add preamble info to composited products
		if ( version_compare( $woocommerce->version, '2.1.0' ) >= 0 ) {
			add_filter( 'woocommerce_cart_item_name', array( $this, 'woo_bto_before_in_cart_product_title' ), 10, 3 );
			add_filter( 'woocommerce_order_item_name', array( $this, 'woo_bto_before_order_table_product_title' ), 10, 2 );
		} else {
			add_filter( 'woocommerce_in_cart_product_title', array( $this, 'woo_bto_before_in_cart_product_title' ), 10, 3 );
			add_filter( 'woocommerce_checkout_product_title', array( $this, 'woo_bto_before_pre_21_product_title' ), 10, 2 );
			add_filter( 'woocommerce_order_table_product_title', array( $this, 'woo_bto_before_order_table_product_title' ), 10, 2 );
			add_filter( 'woocommerce_get_product_from_item', array( $this, 'woo_bto_get_product_from_item' ), 10, 3 );
			add_filter( 'woocommerce_order_product_title', array( $this, 'woo_bto_before_pre_21_product_title' ), 10, 2 );
		}

		// Change the tr class attributes when displaying bundled items in templates
		if ( version_compare( $woocommerce->version, '2.1.0' ) >= 0 ) {
			add_filter( 'woocommerce_cart_item_class', array( $this, 'woo_bto_table_item_class' ), 10, 3 );
			add_filter( 'woocommerce_order_item_class', array( $this, 'woo_bto_table_item_class' ), 10, 3 );
		} else {
		// Deprecated
			add_filter( 'woocommerce_cart_table_item_class', array( $this, 'woo_bto_table_item_class' ), 10, 3 );
			add_filter( 'woocommerce_order_table_item_class', array( $this, 'woo_bto_table_item_class' ), 10, 3 );
			add_filter( 'woocommerce_checkout_table_item_class', array( $this, 'woo_bto_table_item_class' ), 10, 3 );
		}

		// Composite containers should not affect order status
		add_filter( 'woocommerce_order_item_needs_processing', array( $this, 'woo_bto_container_items_need_no_processing' ), 10, 3 );

		// Modify order items to include composite meta - TODO: 3rd argument
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'woo_bto_add_order_item_meta' ), 10, 2 );

		// Hide composite configuration metadata in order line items
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'woo_bto_hidden_order_item_meta' ) );

		// Filter cart widget items
		add_filter( 'woocommerce_widget_cart_item_visible', array( $this, 'woo_bto_cart_widget_filter' ), 10, 3 );

		// Filter cart item count
		add_filter( 'woocommerce_cart_contents_count',  array( $this, 'woo_bto_cart_contents_count' ) );

		// Filter admin dashboard item count
		add_filter( 'woocommerce_get_item_count',  array( $this, 'woo_bto_dashboard_recent_orders_count' ), 10, 3 );

		// Support for Product Addons
		add_action( 'woocommerce_composite_product_add_to_cart', array( $this, 'woo_bto_addons_display_support' ), 10, 2 );
		add_filter( 'product_addons_field_prefix', array( $this, 'woo_bto_addons_cart_prefix' ), 10, 2 );

		// Support for NYP
		add_action( 'woocommerce_composite_product_add_to_cart', array( $this, 'woo_bto_nyp_display_support' ), 9, 2 );
		add_filter( 'nyp_field_prefix', array( $this, 'woo_bto_nyp_cart_prefix' ), 10, 2 );

		// Put back cart item data to allow re-ordering of composites
		add_filter( 'woocommerce_order_again_cart_item_data', array( $this, 'woo_bto_order_again' ), 10, 3 );

		// Debug
		// add_action('woocommerce_before_cart_contents', array($this, 'woo_bto_before_cart') );
		// add_action('woocommerce_before_mini_cart', array($this, 'woo_bto_before_cart') );

	}

	function woo_bto_init() {

		load_plugin_textdomain( 'woocommerce-bto', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}

	/**
	 * Reinialize cart item data for re-ordering purchased orders
	 * @param  mixed 	$cart_item_data
	 * @param  mixed 	$order_item
	 * @param  WC_Order $order
	 * @return mixed
	 */
	function woo_bto_order_again( $cart_item_data, $order_item, $order ) {

			if ( isset( $order_item[ 'composite_parent' ] ) && isset( $order_item[ 'composite_data' ] ) )
				$cart_item_data[ 'is_composited' ] = 'yes';

			if ( isset( $order_item[ 'composite_children' ] ) && isset( $order_item[ 'composite_data' ] ) )
				$cart_item_data[ 'composite_data' ] = maybe_unserialize( $order_item[ 'composite_data' ] );

			return $cart_item_data;
		}

	/**
	 * Outputs add-ons for composited products
	 * @param  int $product_id
	 * @param  int $item_id
	 * @return void
	 */
	function woo_bto_addons_display_support( $product_id, $item_id ) {

		global $Product_Addon_Display;

		if ( ! empty( $Product_Addon_Display ) && version_compare( $Product_Addon_Display->version, '2.1.3' ) > 0 )
			$Product_Addon_Display->display( $product_id, $item_id . '-' );

	}

	/**
	 * Outputs nyp markup
	 * @param  int $product_id
	 * @param  int $item_id
	 * @return void
	 */
	function woo_bto_nyp_display_support( $product_id, $item_id ) {

		global $Name_Your_Price_Display;

		if ( ! empty( $this->show_product_filter_params ) && ! $this->show_product_filter_params[ 'per_product_pricing' ] )
			return;

		if ( ! empty( $Name_Your_Price_Display ) ) {
			$Name_Your_Price_Display->display_price_input( $product_id, '-' . $item_id );
		}

	}

	/**
	 * Sets a prefix for unique add-ons
	 * @param  string 	$prefix
	 * @param  int 		$product_id
	 * @return string
	 */
	function woo_bto_addons_cart_prefix( $prefix, $product_id ) {

		if ( ! empty( $this->addons_prefix ) )
			return $this->addons_prefix . '-';

		return $prefix;
	}

	/**
	 * Sets a prefix for unique nyp
	 * @param  string 	$prefix
	 * @param  int 		$product_id
	 * @return string
	 */
	function woo_bto_nyp_cart_prefix( $prefix, $product_id ) {

		if ( ! empty( $this->nyp_prefix ) )
			return '-' . $this->nyp_prefix;

		return $prefix;
	}

	/**
	 * For debugging use only
	 * @return void
	 */
	function woo_bto_before_cart() {

		global $woocommerce;

		$cart = $woocommerce->cart->get_cart();

		echo 'Cart Contents Total: ' . $woocommerce->cart->cart_contents_total . '<br/>';
		echo 'Cart Tax Total: ' . $woocommerce->cart->tax_total . '<br/>';
		echo 'Cart Total: ' . $woocommerce->cart->get_cart_total() . '<br/>';

		foreach ( $cart as $key => $data ) {
			echo '<br/>Cart Item - '.$key.' ('.count($data).' items):<br/>';

			echo 'Price: ' . $data['data']->get_price();
			echo '<br/>';

			if ( isset( $data['data']->per_product_pricing ) ) {
				echo 'per product pricing: '; echo $data['data']->per_product_pricing; echo ' | ';
			}
			if ( isset( $data['data']->per_product_shipping ) ) {
				echo 'per product shipping: '; echo $data['data']->per_product_shipping; echo ' | ';
			}

			echo 'virtual: '; echo $data['data']->virtual; echo ' | ';

			foreach ( $data as $datakey => $value ) {
				print_r ( $datakey );
				if (is_numeric($value) || is_string($value)) {
					echo ': '.$value;
				}
				elseif ( $datakey == 'composite_children' ) {
					echo ': ';
					print_r($value);
				}
				echo ' | ';
			}
		}
	}

	/**
	 * Do not show composited items
	 * @param  bool 	$show
	 * @param  array 	$cart_item
	 * @param  string 	$cart_item_key
	 * @return bool
	 */
	function woo_bto_cart_widget_filter( $show, $cart_item, $cart_item_key ) {

		global $woocommerce;

		if ( isset( $cart_item[ 'composite_item' ] ) ) {
			return false;
		}

		return $show;

	}

	/**
	 * Filters the reported number of admin dashboard recent order items - counts only composite containers
	 * @param  int 			$count
	 * @param  string 		$type
	 * @param  WC_Order 	$order
	 * @return int
	 */
	function woo_bto_dashboard_recent_orders_count( $count, $type, $order ) {

		global $woocommerce;

		$subtract = 0;

		foreach ( $order->get_items() as $order_item ) {

			if ( isset( $order_item[ 'composite_item' ] ) ) {

				$subtract += $order_item['qty'];

			}
		}

		return $count - $subtract;

	}

	/**
	 * Filters the reported number of cart items - counts only composite containers
	 * @param  int 			$count
	 * @param  WC_Order 	$order
	 * @return int
	 */
	function woo_bto_cart_contents_count( $count ) {

		global $woocommerce;

		$cart 		= $woocommerce->cart->get_cart();
		$subtract 	= 0;

		foreach ( $cart as $key => $value ) {

			if ( isset( $value[ 'composite_item' ] ) ) {

				$subtract += $value['quantity'];

			}
		}

		return $count - $subtract;

	}

	/**
	 * Adds composite info to order items - TODO: add $cart_item_key
	 * @param  int 		$order_item_id
	 * @param  array 	$cart_item_values
	 * @param  string 	$cart_item_key
	 * @return void
	 */
	function woo_bto_add_order_item_meta( $order_item_id, $cart_item_values ) {

		global $woocommerce;

		if ( isset( $cart_item_values[ 'composite_children' ] ) && ! empty( $cart_item_values[ 'composite_children' ] ) ) {

			woocommerce_add_order_item_meta( $order_item_id, '_composite_children', $cart_item_values[ 'composite_children' ] );

			if ( ! empty( $cart_item_values[ 'data' ]->per_product_pricing ) )
				woocommerce_add_order_item_meta( $order_item_id, '_per_product_pricing', $cart_item_values[ 'data' ]->per_product_pricing );
		}

		if ( isset( $cart_item_values[ 'composite_parent' ] ) && ! empty( $cart_item_values[ 'composite_parent' ] ) ) {

			woocommerce_add_order_item_meta( $order_item_id, '_composite_parent', $cart_item_values[ 'composite_parent' ] );

			// find parent in cart - not really necessary since we know its going to be there

			$product_key = $woocommerce->cart->find_product_in_cart( $cart_item_values[ 'composite_parent' ] );

			if ( ! empty( $product_key ) ) {
				$product_name = $woocommerce->cart->cart_contents[ $product_key ][ 'data' ]->post->post_title;
				woocommerce_add_order_item_meta( $order_item_id, __( 'Part of', 'woocommerce-bto' ), __( $product_name ) );
			}

		}

		if ( isset( $cart_item_values[ 'composite_item' ] ) && ! empty( $cart_item_values[ 'composite_item' ] ) ) {
			woocommerce_add_order_item_meta( $order_item_id, '_composite_item', $cart_item_values[ 'composite_item' ] );
		}

		if ( isset( $cart_item_values[ 'composite_data' ] ) && ! empty( $cart_item_values[ 'composite_data' ] ) ) {
			// TODO: uncomment in WC 2.1
			//woocommerce_add_order_item_meta( $order_item_id, '_composite_cart_key', $cart_item_key );
			woocommerce_add_order_item_meta( $order_item_id, '_composite_data', $cart_item_values[ 'composite_data' ] );
		}
	}

	/**
	 * Adds item title preambles to order-details template ( Composite Attribute Descriptions )
	 * @param  string 	$content
	 * @param  array 	$order_item
	 * @return string
	 */
	function woo_bto_before_order_table_product_title( $content, $order_item ) {

		if ( isset( $order_item[ 'composite_item' ] ) && ! empty( $order_item[ 'composite_item' ] ) ) {
			$item_id 		= $order_item[ 'composite_item' ];
			$composite_data = maybe_unserialize( $order_item[ 'composite_data' ] );
			$item_title 	= $composite_data[ $item_id ][ 'title' ];
			return '<span class="before-product-name">' . $item_title . ': </span>' . $content;
		}

		return $content;
	}

	/**
	 * Adds order item title preambles to order-details template ( Composite Attribute Descriptions )
	 * @param  string 	$content
	 * @param  array 	$cart_item_values
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	function woo_bto_before_in_cart_product_title( $content, $cart_item_values, $cart_item_key ) {

		if ( isset( $cart_item_values[ 'composite_item' ] ) && ! empty( $cart_item_values[ 'composite_item' ] ) ) {
			$item_id 	= $cart_item_values[ 'composite_item' ];
			$item_title = $cart_item_values[ 'composite_data' ][ $item_id ][ 'title' ];
			return '<span class="before-product-name">' . $item_title . ': </span>' . $content;
		}

		return $content;
	}

	/**
	 * Adds order item title preambles to order-details template ( Composite Attribute Descriptions ) TODO: delete
	 * @param  string 		$content
	 * @param  WC_Product 	$product
	 * @return string
	 */
	function woo_bto_before_pre_21_product_title( $content, $product ) {

		if ( isset( $product->property_title ) && ! empty( $product->property_title ) ) {
			$item_title = $product->property_title;
			return '<span class="before-product-name">' . $item_title . ': </span>' . $content;
		}

		return $content;
	}

	/**
	 * Copies composite product data to the product - not needed after WC 2.1 - TODO: delete
	 * @param  WC_Product $product
	 * @param  array $item
	 * @param  WC_Order $order
	 * @return void
	 */
	function woo_bto_get_product_from_item( $product, $item, $order ) {
		if ( isset( $item[ 'composite_item' ] ) && ! empty( $item[ 'composite_item' ] ) ) {
			$item_id 		= $item[ 'composite_item' ];
			$composite_data = maybe_unserialize( $item[ 'composite_data' ] );
			$item_title 	= $composite_data[ $item_id ][ 'title' ];

			$product->property_title = $item_title;
		}

		return $product;
	}

	/**
	 * Modifies the subtotal of order-items (order-details.php) depending on the bundles's pricing strategy
	 * @param  string 	$subtotal
	 * @param  array 	$item
	 * @param  WC_Order $order
	 * @return string
	 */
	function woo_bto_order_item_subtotal( $subtotal, $item, $order ) {

		// If it's a composited item
		if ( isset( $item[ 'composite_parent' ] ) ) {

			$composite_data = $item[ 'composite_data' ];

			// find composite parent
			$parent_item = '';

			foreach ( $order->get_items( 'line_item' ) as $order_item ) {

				if ( $order_item[ 'composite_data' ] == $composite_data && isset( $order_item[ 'composite_children' ] ) ) {
					$parent_item = $order_item;
				}
			}

			if ( $parent_item[ 'per_product_pricing' ] == 'no' && $item[ 'line_subtotal' ] == 0 )
				return '';
			else
				return  __( 'Option subtotal', 'woocommerce-bto' ) . ': ' . $subtotal;
		}

		// If it's a parent item
		if ( isset( $item[ 'composite_children' ] ) ) {

			if ( isset( $item[ 'subtotal_updated' ] ) )
				return $subtotal;

			foreach ( $order->get_items( 'line_item' ) as $order_item ) {

				if ( $order_item[ 'composite_data' ] == $item[ 'composite_data' ] && isset( $order_item[ 'composite_parent' ] ) ) {

					$item[ 'line_subtotal' ] 		+= $order_item[ 'line_subtotal' ];
					$item[ 'line_subtotal_tax' ] 	+= $order_item[ 'line_subtotal_tax' ];
				}
			}

			$item[ 'subtotal_updated' ] = 'yes';

			return $order->get_formatted_line_subtotal( $item );
		}

		return $subtotal;
	}

	/**
	 * Outputs a formatted subtotal
	 * @param  WC_Product 	$product
	 * @param  double 		$subtotal
	 * @return string
	 */
	function format_product_subtotal( $product, $subtotal ) {

		global $woocommerce;

		$cart = $woocommerce->cart;

		$taxable = $product->is_taxable();

		// Taxable
		if ( $taxable ) {

			if ( $cart->tax_display_cart == 'excl' ) {

				$product_subtotal = woocommerce_price( $subtotal );

				if ( $cart->prices_include_tax && $cart->tax_total > 0 )
					$product_subtotal .= ' <small class="tax_label">' . $woocommerce->countries->ex_tax_or_vat() . '</small>';

			} else {

				$product_subtotal = woocommerce_price( $subtotal );

				if ( ! $cart->prices_include_tax && $cart->tax_total > 0 )
					$product_subtotal .= ' <small class="tax_label">' . $woocommerce->countries->inc_tax_or_vat() . '</small>';
			}

		// Non-taxable
		} else {
			$product_subtotal = woocommerce_price( $subtotal );
		}

		return $product_subtotal;
	}

	/**
	 * Modifies the cart.php & review-order.php templates formatted html prices visibility depending on pricing strategy
	 * @param  string 	$price
	 * @param  array 	$values
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	function woo_bto_cart_item_price( $price, $values, $cart_item_key ) {

		global $woocommerce;

		if ( isset( $values[ 'composite_parent' ] ) && ! empty( $values[ 'composite_parent' ] ) ) {

			$parent_cart_key = $values[ 'composite_parent' ];

			if ( $woocommerce->cart->cart_contents[ $parent_cart_key ][ 'data' ]->per_product_pricing == 'no' && $values[ 'data' ]->price == 0 )
				return '';
		}

		if ( isset( $values[ 'composite_children' ] ) && ! empty( $values[ 'composite_children' ] ) ) {

			if ( $values[ 'data' ]->per_product_pricing == 'yes' ) {

				$composited_items_price = 0;
				$composite_price 		= get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? $values[ 'data' ]->get_price_excluding_tax() : $values[ 'data' ]->get_price_including_tax();

				foreach ( $values[ 'composite_children' ] as $composite_child_key ) {
					$composited_items_price += get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? $woocommerce->cart->cart_contents[ $composite_child_key ][ 'data' ]->get_price_excluding_tax( $woocommerce->cart->cart_contents[ $composite_child_key ][ 'quantity' ] / $values[ 'quantity' ] ) : $woocommerce->cart->cart_contents[ $composite_child_key ][ 'data' ]->get_price_including_tax( $woocommerce->cart->cart_contents[ $composite_child_key ][ 'quantity' ] / $values[ 'quantity' ] );
				}

				$price = $composite_price + $composited_items_price;
				return woocommerce_price( $price );

			}
		}

		return $price;
	}


	/**
	 * Modifies the cart.php & review-order.php templates formatted subtotal appearance depending on pricing strategy
	 * @param  string 	$price
	 * @param  array 	$values
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	function woo_bto_item_subtotal( $subtotal, $values, $cart_item_key ) {

		global $woocommerce;

		if ( isset( $values[ 'composite_parent' ] ) && ! empty( $values[ 'composite_parent' ] ) ) {

			$parent_cart_key = $values[ 'composite_parent' ];

			if ( $woocommerce->cart->cart_contents[ $parent_cart_key ][ 'data' ]->per_product_pricing == 'no' && $values[ 'data' ]->price == 0 )
				return '';
			else
				return __( 'Option subtotal', 'woocommerce-bto' ) . ': ' . $subtotal;
		}

		if ( isset( $values[ 'composite_children' ] ) && ! empty( $values[ 'composite_children' ] ) ) {

			$composited_items_price = 0;
			$composite_price 		= get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? $values[ 'data' ]->get_price_excluding_tax( $values[ 'quantity' ] ) : $values[ 'data' ]->get_price_including_tax( $values[ 'quantity' ] );

			foreach ( $values[ 'composite_children' ] as $composite_child_key ) {
				$composited_items_price += get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? $woocommerce->cart->cart_contents[ $composite_child_key ][ 'data' ]->get_price_excluding_tax( $woocommerce->cart->cart_contents[ $composite_child_key ][ 'quantity' ] ) : $woocommerce->cart->cart_contents[ $composite_child_key ][ 'data' ]->get_price_including_tax( $woocommerce->cart->cart_contents[ $composite_child_key ][ 'quantity' ] );
			}

			$subtotal = $composite_price + $composited_items_price;

			return $this->format_product_subtotal( $values[ 'data' ], $subtotal );

		}

		return $subtotal;
	}

	/**
	 * Keeps composited items' quantities in sync with container item
	 * @param  string  $cart_item_key
	 * @param  integer $quantity
	 * @return void
	 */
	function woo_bto_update_quantity_in_cart( $cart_item_key, $quantity = 0 ) {

		global $woocommerce;

		if ( isset( $woocommerce->cart->cart_contents[ $cart_item_key ] ) && ! empty( $woocommerce->cart->cart_contents[ $cart_item_key ] ) ) {

			if ( $quantity == 0 || $quantity < 0 ) {
				$quantity = 0;
			} else {
				$quantity = $woocommerce->cart->cart_contents[ $cart_item_key ][ 'quantity' ];
			}

			$composite_children = ! empty( $woocommerce->cart->cart_contents[ $cart_item_key ][ 'composite_children' ] ) ? $woocommerce->cart->cart_contents[ $cart_item_key ][ 'composite_children' ] : '';

			if ( ! empty( $composite_children ) ) {

				$composite_quantity = $quantity;

				// change the quantity of all composited items that belong to the same config
				foreach ( $composite_children as $child_key ) {

					$child_item = $woocommerce->cart->cart_contents[ $child_key ];

					if ( ! $child_item )
						continue;

					if ( $child_item[ 'data' ]->is_sold_individually() && $quantity > 0 ) {

						$woocommerce->cart->set_quantity( $child_key, 1 );

					} else {

						$child_item_id 	= $woocommerce->cart->cart_contents[ $child_key ][ 'composite_item' ];
						$child_quantity = $woocommerce->cart->cart_contents[ $child_key ][ 'composite_data' ][ $child_item_id ][ 'quantity' ];

						$woocommerce->cart->set_quantity( $child_key, $child_quantity * $composite_quantity  );
					}
				}
			}
		}
	}

	/**
	 * Changes the tr class of composited items in all templates to allow their styling
	 * @param  string 	$classname
	 * @param  array 	$values
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	function woo_bto_table_item_class( $classname, $values, $cart_item_key ) {
		if ( isset( $values[ 'composite_data' ] ) && isset( $values[ 'composite_parent' ] ) && ! empty( $values[ 'composite_parent' ] ) )
			return $classname . ' composited_table_item';
		return $classname;
	}

	/**
	 * Composite Containers should not affect order status - let it be decided by composited items only
	 * @param  bool 		$is_needed
	 * @param  WC_Product 	$product
	 * @param  int 			$order_id
	 * @return bool
	 */
	function woo_bto_container_items_need_no_processing( $is_needed, $product, $order_id ) {
		if ( $product->is_type( 'bto' ) ) {
			return false;
		}
		return $is_needed;
	}

	/**
	 * Composited items can't be removed individually from the cart
	 * @param  string 	$link
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	function woo_bto_cart_item_remove_link( $link, $cart_item_key ) {
		global $woocommerce;

		if ( isset( $woocommerce->cart->cart_contents[ $cart_item_key ][ 'composite_data' ] ) && isset( $woocommerce->cart->cart_contents[ $cart_item_key ][ 'composite_parent' ] ) && ! empty( $woocommerce->cart->cart_contents[ $cart_item_key ][ 'composite_parent' ] ) )
			return '';

		return $link;
	}

	/**
	 * Composited item quantities can't be changed individually
	 * @param  string 	$quantity
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	function woo_bto_cart_item_quantity( $quantity, $cart_item_key ) {
		global $woocommerce;

		if ( isset( $woocommerce->cart->cart_contents[ $cart_item_key ][ 'composite_data' ] ) && isset( $woocommerce->cart->cart_contents[ $cart_item_key ][ 'composite_parent' ] ) && ! empty( $woocommerce->cart->cart_contents[ $cart_item_key ][ 'composite_parent' ] ) ) {
			return $woocommerce->cart->cart_contents[ $cart_item_key ][ 'quantity' ];
		}
		return $quantity;
	}


	/**
	 * Modifies cart item data - important for the first calculation of totals only
	 * @param  array $cart_item_data
	 * @param  string $cart_item_key
	 * @return array
	 */
	function woo_bto_add_cart_item_filter( $cart_item_data, $cart_item_key ) {

		global $woocommerce;

		$cart_contents = $woocommerce->cart->get_cart();

		if ( isset( $cart_item_data[ 'composite_children' ] ) && ! empty( $cart_item_data[ 'composite_children' ] ) ) {

			// per-product pricing & shipping
			$per_product_pricing = $cart_item_data[ 'data' ]->per_product_pricing;

			if ( $per_product_pricing == 'yes' ) {
				$cart_item_data['data']->price = 0;
			}
		}

		if ( isset( $cart_item_data[ 'composite_parent' ] ) && ! empty( $cart_item_data[ 'composite_parent' ] ) ) {

			$parent_cart_key = $cart_item_data[ 'composite_parent' ];

			// workaround for < WC 2.1 - TODO: delete
			$cart_item_data[ 'data' ]->property_title = $cart_item_data[ 'composite_data' ][ $cart_item_data[ 'composite_item' ] ][ 'title' ];

			// now modify item virtual status and price depending on composite pricing and shipping strategies

			// per-product pricing & shipping
			$per_product_pricing 	= $cart_contents[ $parent_cart_key ][ 'data' ]->per_product_pricing;
			$per_product_shipping 	= $cart_contents[ $parent_cart_key ][ 'data' ]->per_product_shipping;

			if ( $per_product_pricing == 'no' ) {
				$cart_item_data['data']->price = 0;
			}
			else {
				$cart_item_data['data']->price = ( double ) $cart_item_data[ 'composite_data' ][ $cart_item_data[ 'composite_item' ] ][ 'price' ];
			}

			if ( $per_product_shipping == 'no' )
				$cart_item_data['data']->virtual = 'yes';

		}

		return $cart_item_data;

	}

	/**
	 * Load all composite-related session data
	 * @param  array 	$cart_item
	 * @param  array 	$item_session_values
	 * @return void
	 */
	function woo_bto_get_cart_data_from_session( $cart_item, $item_session_values ) {

		global $woocommerce;

		$cart_contents = $woocommerce->cart->get_cart();

		if ( isset( $item_session_values[ 'composite_data' ] ) ) {

			// load composite data
			$cart_item[ 'composite_data' ] = $item_session_values[ 'composite_data' ];

		}

		if ( isset( $item_session_values[ 'composite_children' ] ) && ! empty( $item_session_values[ 'composite_children' ] ) ) {
			$cart_item[ 'composite_children' ] 	= $item_session_values[ 'composite_children' ];

			// per-product pricing & shipping
			$per_product_pricing 	=  $cart_item[ 'data' ]->per_product_pricing;

			if ( $per_product_pricing == 'yes' ) {
				$cart_item['data']->price = 0;
			}
		}

		if ( isset( $item_session_values[ 'composite_parent' ] ) && ! empty( $item_session_values[ 'composite_parent' ] ) ) {

			$cart_item[ 'composite_parent' ] = $item_session_values[ 'composite_parent' ];

			// load dynamic pricing permission
			if ( isset( $item_session_values[ 'dynamic_pricing_allowed' ] ) )
				$cart_item[ 'dynamic_pricing_allowed' ] = $item_session_values[ 'dynamic_pricing_allowed' ];

			// load item id
			if ( isset( $item_session_values[ 'composite_item' ] ) )
				$cart_item[ 'composite_item' ] = $item_session_values[ 'composite_item' ];

			$parent_cart_key = $cart_item[ 'composite_parent' ];

			// workaround for < WC 2.1 - TODO: delete
			$cart_item[ 'data' ]->property_title = $cart_item[ 'composite_data' ][ $cart_item[ 'composite_item' ] ][ 'title' ];

			// now modify item virtual status and price depending on composite pricing and shipping strategies

			// per-product pricing & shipping
			$per_product_pricing 	= $cart_contents[ $parent_cart_key ][ 'data' ]->per_product_pricing;
			$per_product_shipping 	= $cart_contents[ $parent_cart_key ][ 'data' ]->per_product_shipping;

			if ( $per_product_pricing == 'no' ) {
				$cart_item['data']->price = 0;
			}
			else {
				$cart_item['data']->price = ( double ) $cart_item[ 'composite_data' ][ $cart_item[ 'composite_item' ] ][ 'price' ];
			}

			if ( $per_product_shipping == 'no' )
				$cart_item['data']->virtual = 'yes';

		}


		return $cart_item;
	}

	/**
	 * Adds composited items to the cart.
	 * @param  string 	$item_cart_key
	 * @param  int 		$product_id
	 * @param  int 		$quantity
	 * @param  int 		$variation_id
	 * @param  array 	$variation
	 * @param  array 	$cart_item_data
	 * @return void
	 */
	function woo_bto_add_items_to_cart( $item_cart_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		global $woocommerce;

		// Runs when adding container item - adds composited items
		if ( isset( $cart_item_data[ 'composite_data' ] ) && ! isset( $cart_item_data[ 'composite_parent' ] ) ) {

			// Only attempt to add composited items if they don't already exist
			foreach ( $woocommerce->cart->cart_contents as $cart_key => $cart_value ) {
				if ( isset( $cart_value[ 'composite_data' ] ) && isset( $cart_value[ 'composite_parent' ] ) && $item_cart_key == $cart_value[ 'composite_parent' ] ) {
					return;
				}
			}

			$GLOBALS[ 'composite_children' ] = array();

			// This id is unique, so that bundled and non-bundled versions of the same product will be added separately to the cart.
			$composited_item_cart_data = array( 'composite_item' => '', 'dynamic_pricing_allowed' => 'no', 'composite_parent' => $item_cart_key, 'composite_data' => $cart_item_data[ 'composite_data' ] );

			// Now add all items - yay!
			foreach ( $cart_item_data[ 'composite_data' ] as $item_id => $composite_item_data ) {

				$composited_item_cart_data[ 'composite_item' ] = $item_id;

				$composited_product_id 	= $composite_item_data[ 'product_id' ];
				$variation_id 			= '';
				$variations 			= array();

				if ( $composite_item_data[ 'type' ] == 'none' )
					continue;

				$item_quantity 		= $composite_item_data[ 'quantity' ];
				$composite_quantity = ( isset( $_REQUEST[ 'quantity' ] ) && (int) $_REQUEST[ 'quantity' ] > 0 ) ? (int) $_REQUEST[ 'quantity' ] : 1;
				$quantity			= $item_quantity * $composite_quantity;

				if ( $composite_item_data[ 'type' ] == 'variable' ) {

					$variation_id 	= ( int ) $composite_item_data[ 'variation_id' ];
					$variations		= $composite_item_data[ 'attributes' ];
				}

				// Set addons prefix
				$this->addons_prefix = $this->nyp_prefix = $item_id;
				// Add to cart
				$woocommerce->cart->add_to_cart( $composited_product_id, $quantity, $variation_id, $variations, $composited_item_cart_data );
				// Reset addons prefix
				$this->addons_prefix = $this->nyp_prefix = '';
			}

			$woocommerce->cart->cart_contents[ $item_cart_key ][ 'composite_children' ] = $GLOBALS[ 'composite_children' ];
			unset( $GLOBALS[ 'composite_children' ] );
		}

		// Runs when adding bundled items - adds child data to parent
		if ( isset( $cart_item_data[ 'composite_parent' ] ) && ! empty( $cart_item_data[ 'composite_parent' ] ) ) {

			$parent_item = $woocommerce->cart->cart_contents[ $cart_item_data[ 'composite_parent' ] ];

			if ( ! empty( $parent_item ) ) {
				if ( ! in_array( $item_cart_key, $GLOBALS[ 'composite_children' ] ) )
					$GLOBALS[ 'composite_children' ][] = $item_cart_key;
			}
		}


	}

	/**
	 * Hides composite metadata
	 * @param  array $hidden
	 * @return array
	 */
	function woo_bto_hidden_order_item_meta( $hidden ) {
		return array_merge( $hidden, array( '_composite_parent', '_composite_item', '_composite_total', '_composite_cart_key', '_per_product_pricing' ) );
	}

	/**
	 * Adds configuration-specific cart-item data
	 * @param  array 	$cart_item_data
	 * @param  int 		$product_id
	 * @return void
	 */
	function woo_bto_add_cart_item_data( $cart_item_data, $product_id ) {

		// Get product type
		$terms 			= get_the_terms( $product_id, 'product_type' );
		$product_type 	= ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

		if ( $product_type == 'bto' && isset( $_REQUEST[ 'add-product-to-cart' ] ) && is_array( $_REQUEST[ 'add-product-to-cart' ] ) ) {

			// Create a unique array with the composite configuration
			$composite_config = array();

			// Get composite data
			$bto_data = maybe_unserialize( get_post_meta( $product_id, '_bto_data', true ) );

			foreach ( $_REQUEST[ 'add-product-to-cart' ] as $bundled_item_id => $bundled_product_id ) {

				// Get product type
				$terms 					= get_the_terms( $bundled_product_id, 'product_type' );
				$bundled_product_type 	= ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

				$composite_config[ $bundled_item_id ][ 'product_id' ] 	= $bundled_product_id;
				$composite_config[ $bundled_item_id ][ 'quantity' ] 	= (int) $_REQUEST[ 'item_quantity' ][ $bundled_item_id ];
				$composite_config[ $bundled_item_id ][ 'title' ] 		= $bto_data[ $bundled_item_id ][ 'title' ];
				$composite_config[ $bundled_item_id ][ 'quantity_min' ] = $bto_data[ $bundled_item_id ][ 'quantity_min' ];
				$composite_config[ $bundled_item_id ][ 'quantity_max' ] = $bto_data[ $bundled_item_id ][ 'quantity_max' ];
				$composite_config[ $bundled_item_id ][ 'discount' ] 	= isset( $bto_data[ $bundled_item_id ][ 'discount' ] ) ? $bto_data[ $bundled_item_id ][ 'discount' ] : 0;
				$composite_config[ $bundled_item_id ][ 'optional' ] 	= $bto_data[ $bundled_item_id ][ 'optional' ];

				if ( $bundled_product_id === '0' ) {
					$composite_config[ $bundled_item_id ][ 'type' ] 	= 'none';
					$composite_config[ $bundled_item_id ][ 'price' ]	= 0;
					continue;
				}

				if ( $bundled_product_type == 'simple' ) {

					$composite_config[ $bundled_item_id ][ 'type' ] 	= 'simple';

					$price 			= get_post_meta( $bundled_product_id, '_price', true );
					$regular_price 	= get_post_meta( $bundled_product_id, '_regular_price', true );

					$product_regular_price 	= empty( $regular_price ) ? ( double ) $price : ( double ) $regular_price;
					$product_price 			= empty( $bto_data[ $bundled_item_id ][ 'discount' ] ) || empty( $regular_price ) ? ( double ) $price : $product_regular_price * ( 100 - $bto_data[ $bundled_item_id ][ 'discount' ] ) / 100;

					$composite_config[ $bundled_item_id ][ 'price' ] = $product_price;

				}
				elseif ( $bundled_product_type == 'variable' ) {

					$attributes_config 	= array();
					$attributes 		= ( array ) maybe_unserialize( get_post_meta( $bundled_product_id, '_product_attributes', true ) );

					foreach ( $attributes as $attribute ) {

						if ( ! $attribute['is_variation'] )
							continue;

						$taxonomy 	= 'attribute_' . sanitize_title( $attribute[ 'name' ] );

						// has already been checked for validity in function 'woo_bto_validation'
						$value 		= sanitize_title( trim( stripslashes( $_REQUEST[ $taxonomy ][ $bundled_item_id ] ) ) );

						if ( $attribute[ 'is_taxonomy' ] )
							$attributes_config[ esc_html( $attribute['name'] ) ] = $value;
						else {
						    // For custom attributes, get the name from the slug
						    $options = array_map( 'trim', explode( '|', $attribute[ 'value' ] ) );
						    foreach ( $options as $option ) {
						    	if ( sanitize_title( $option ) == $value ) {
						    		$value = $option;
						    		break;
						    	}
						    }
						     $attributes_config[ esc_html( $attribute[ 'name' ] ) ] = $value;
						}

					}

					$composite_config[ $bundled_item_id ][ 'type' ] 		= 'variable';
					$composite_config[ $bundled_item_id ][ 'variation_id' ] = $_REQUEST[ 'variation_id' ][ $bundled_item_id ];
					$composite_config[ $bundled_item_id ][ 'attributes' ] 	= $attributes_config;

					$regular_price 	= get_post_meta( $_REQUEST[ 'variation_id' ][ $bundled_item_id ], '_regular_price', true );
					$price 			= get_post_meta( $_REQUEST[ 'variation_id' ][ $bundled_item_id ], '_price', true );

					$variation_regular_price 	= empty( $regular_price ) ? ( double ) $price : ( double ) $regular_price;
					$variation_price 			= empty( $bto_data[ $bundled_item_id ][ 'discount' ] ) || empty( $regular_price ) ? ( double ) $price : $regular_price * ( 100 - $bto_data[ $bundled_item_id ][ 'discount' ] ) / 100;

					$composite_config[ $bundled_item_id ][ 'price' ] = $variation_price;

				}
			}

			$cart_item_data[ 'composite_data' ] = $composite_config;

			// Prepare additional data for later use
			$cart_item_data[ 'composite_children' ] = array();

			return $cart_item_data;

		} else {

			return $cart_item_data;
		}

	}

	/**
	 * Used to check stock before attempting to call the add_to_cart function
	 * Some double checking happens, but it's better than partially adding composite products to the cart
	 * @param  int 	$product_id
	 * @param  int 	$variation_id
	 * @param  int 	$quantity
	 * @param  bool $exclude_cart
	 * @param  bool $silent
	 * @return bool
	 */
	function validate_stock( $product_id, $variation_id, $quantity, $exclude_cart, $silent ) {

		global $woocommerce;

		if ( $variation_id > 0 ) {
			$product_data = get_product( $variation_id, array( 'product_type' => 'variation') );
		} else {
			$product_data = get_product( $product_id, array( 'product_type' => 'simple') );
		}

		// Stock check - only check if we're managing stock and backorders are not allowed.
		if ( ! $product_data->is_in_stock() ) {
			if ( ! $silent )
				$woocommerce->add_error( sprintf( __( 'The configuration you have selected cannot be added to the cart since "%s" is out of stock.', 'woocommerce-bto' ), $product_data->get_title() ) );
			return false;
		}
		elseif ( ! $product_data->has_enough_stock( $quantity ) ) {
			if ( ! $silent )
				$woocommerce->add_error( sprintf(__( 'The configuration you have selected cannot be added to the cart since there is not enough stock of "%s". We have %s in stock.', 'woocommerce-bto' ), $product_data->get_title(), $product_data->get_stock_quantity() ));
			return false;
		}

		// Stock check - this time accounting for whats already in-cart.
		if ( $exclude_cart )
			return true;

		$product_qty_in_cart = $woocommerce->cart->get_cart_item_quantities();

		if ( $product_data->managing_stock() ) {

			// Variations
			if ( $variation_id && $product_data->variation_has_stock ) {

				if ( isset( $product_qty_in_cart[ $variation_id ] ) && ! $product_data->has_enough_stock( $product_qty_in_cart[ $variation_id ] + $quantity ) ) {
					if ( ! $silent )
						$woocommerce->add_error( sprintf(__('<a href="%s" class="button">%s</a>The configuration you have selected cannot be added to the cart since there is not enough stock of "%s" &mdash; we have %s in stock and you already have %s in your cart.', 'woocommerce-bto'), get_permalink(woocommerce_get_page_id('cart')), __('View Cart &rarr;', 'woocommerce'), $product_data->get_title(), $product_data->get_stock_quantity(), $product_qty_in_cart[ $variation_id ] ));
					return false;
				}

			// Products
			} else {

				if ( isset( $product_qty_in_cart[ $product_id ] ) && ! $product_data->has_enough_stock( $product_qty_in_cart[ $product_id ] + $quantity ) ) {
					if ( ! $silent )
						$woocommerce->add_error( sprintf(__('<a href="%s" class="button">%s</a>The configuration you have selected cannot be added to the cart since there is not enough stock of "%s" &mdash; we have %s in stock and you already have %s in your cart.', 'woocommerce-bto'), get_permalink(woocommerce_get_page_id('cart')), __('View Cart &rarr;', 'woocommerce'), $product_data->get_title(), $product_data->get_stock_quantity(), $product_qty_in_cart[ $product_id ] ));
					return false;
				}

			}

		}

		return true;

	}

	/**
	 * Validates that all composited items chosen can be added-to-cart before actually starting to add items
	 * @param  bool 	$add
	 * @param  int 		$product_id
	 * @param  int 		$product_quantity
	 * @return bool
	 */
	function woo_bto_validation( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {

		global $woocommerce;

		// Get product type
		$terms 			= get_the_terms( $product_id, 'product_type' );
		$product_type 	= ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

		// prevent composited items from getting validated - they will be added by the container item
		if ( isset( $cart_item_data[ 'is_composited' ] ) && isset( $_GET['order_again'] ) )
			return false;

		if ( $product_type == 'bto' ) {

			$bto_data 	= maybe_unserialize( get_post_meta( $product_id, '_bto_data', true ) );
			$valid_ids 	= array_keys( $bto_data );

			// Check request and prepare variation stock check data
			$stock_check_data = array();

			foreach ( $valid_ids as $bundled_item_id ) {

				// Check that a product has been selected
				if ( isset( $_REQUEST[ 'add-product-to-cart' ][ $bundled_item_id ] ) && $_REQUEST[ 'add-product-to-cart' ][ $bundled_item_id ] !== '' ) {
					$bundled_product_id = $_REQUEST[ 'add-product-to-cart' ][ $bundled_item_id ];
				} elseif ( isset( $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'product_id' ] ) && isset( $_GET[ 'order_again' ] ) ) {
					$bundled_product_id = $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'product_id' ];
				} else {
					$woocommerce->add_error( sprintf( __( 'Please choose &quot;%s&quot; options&hellip;', 'woocommerce-bto' ), $bto_data[ $bundled_item_id ] [ 'title' ] ) );
					return false;
				}

				// If necessary verify that the component is indeed optional
				if ( $bundled_product_id === '0' ) {
					if ( $bto_data[ $bundled_item_id ][ 'optional' ] == 'yes' ) {
						continue;
					} else {
						$woocommerce->add_error( sprintf( __( 'Please choose &quot;%s&quot; options&hellip;', 'woocommerce-bto' ), $bto_data[ $bundled_item_id ][ 'title' ] ) );
						return false;
					}
				}

				// Prevent people from fucking around
				if ( ! in_array( $bundled_product_id, $bto_data[ $bundled_item_id ][ 'assigned_ids' ] ) ) {
					return false;
				}

				$item_quantity_min = absint( $bto_data[ $bundled_item_id ][ 'quantity_min' ] );
				$item_quantity_max = absint( $bto_data[ $bundled_item_id ][ 'quantity_max' ] );

				// Check quantity
				if ( isset( $_REQUEST[ 'item_quantity' ][ $bundled_item_id ] ) && is_numeric( $_REQUEST[ 'item_quantity' ][ $bundled_item_id ] ) ) {
					$item_quantity = absint( $_REQUEST[ 'item_quantity' ][ $bundled_item_id ] );
				} elseif ( isset( $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'quantity' ] ) && isset( $_GET[ 'order_again' ] ) ) {
					$item_quantity = $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'quantity' ];
				}

				if ( isset( $item_quantity ) && $item_quantity > 0 && $item_quantity >= $item_quantity_min && $item_quantity <= $item_quantity_max )
					$item_quantity = absint( $item_quantity );
				else {
					$woocommerce->add_error( sprintf( __( 'The configuration you have selected cannot be added to the cart. The quantity of &quot;%s&quot; must be between %d and %d.', 'woocommerce-bto' ), $bto_data[$bundled_item_id][ 'title' ], $item_quantity_min, $item_quantity_max ) );
					return false;
				}

				$quantity = $item_quantity * $product_quantity;

				// Get bundled product type
				$terms 					= get_the_terms( $bundled_product_id, 'product_type' );
				$bundled_product_type 	= ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

				if ( $bundled_product_type == 'variable' ) {

					$variation_id = isset( $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'variation_id' ] ) && isset( $_GET[ 'order_again' ] ) ? $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'variation_id' ] : $_REQUEST[ 'variation_id' ][ $bundled_item_id ] ;

					if ( isset( $variation_id ) && is_numeric( $variation_id ) && $variation_id > 1 ) {

						$stock_check_data[ $bundled_product_id ][ 'type' ] = 'variable';

						$variation_stock = get_post_meta( $variation_id, '_stock', true );

						if ( get_post_meta( $variation_id, '_price', true ) === '' ) {
							$woocommerce->add_error( sprintf( __( 'Sorry, the selected variation of &quot;%s&quot; cannot be purchased.', 'woocommerce-bto' ), get_the_title( $bundled_product_id ) ) );
							return false;
						}

						if ( ! isset( $stock_check_data[ $bundled_product_id ][ 'variations' ] ) )
							$stock_check_data[ $bundled_product_id ][ 'variations' ] = array();

						if ( ! isset( $stock_check_data[ $bundled_product_id ][ 'managed_quantities' ] ) )
							$stock_check_data[ $bundled_product_id ][ 'managed_quantities' ] = array();

						if ( ! in_array( $variation_id, $stock_check_data[ $bundled_product_id ][ 'variations' ] ) )
							$stock_check_data[ $bundled_product_id ][ 'variations' ][] = $variation_id;

						// If stock is managed on a variation level
						if ( isset( $variation_stock ) && $variation_stock !== '' ) {

							// If a stock-managed variation is added to the cart multiple times,
							// its stock must be checked for the sum of all quantities
							if ( isset( $stock_check_data[ $bundled_product_id ][ 'managed_quantities' ][ $variation_id ] ) )
								$stock_check_data[ $bundled_product_id ][ 'managed_quantities' ][ $variation_id ] += $quantity;
							else
								$stock_check_data[ $bundled_product_id ][ 'managed_quantities' ][ $variation_id ] = $quantity;

						} else {

							// Non-stock-managed variations of the same item
							// must be stock-checked together
							if ( isset( $stock_check_data[ $bundled_product_id ][ 'quantity' ] ) )
								$stock_check_data[ $bundled_product_id ][ 'quantity' ] += $quantity;
							else {
								$stock_check_data[ $bundled_product_id ][ 'quantity' ] = $quantity;
							}
						}

					}
					else {
    					$woocommerce->add_error( sprintf( __( 'Please choose &quot;%s&quot; options&hellip;', 'woocommerce-bto' ), $bto_data[ $bundled_item_id ][ 'title' ] ) );
						return false;
					}

					// Verify all attributes for the variable product were set

					$attributes = ( array ) maybe_unserialize( get_post_meta( $bundled_product_id, '_product_attributes', true ) );
		    		$variations = array();
		    		$all_set 	= true;

		    		$variation_data = array();

					$custom_fields = get_post_meta( $variation_id );

					// Get the variation attributes from meta
					foreach ( $custom_fields as $name => $value ) {
						if ( ! strstr( $name, 'attribute_' ) )
							continue;

						$variation_data[ $name ] = sanitize_title( $value[0] );
					}

					// Verify all attributes are set and valid
					foreach ( $attributes as $attribute ) {
					    if ( ! $attribute[ 'is_variation' ] )
					    	continue;

					    $taxonomy = 'attribute_' . sanitize_title( $attribute[ 'name' ] );

						if ( ! empty( $_REQUEST[ $taxonomy ][ $bundled_item_id ] ) ) {

					        // Get value from post data
					        // Don't use woocommerce_clean as it destroys sanitized characters
					        $value = sanitize_title( trim( stripslashes( $_REQUEST[ $taxonomy ][ $bundled_item_id ] ) ) );

					        // Get valid value from variation
					        $valid_value = $variation_data[ $taxonomy ];

					        // Allow if valid
					        if ( $valid_value == '' || $valid_value == $value ) {
					            continue;
					        }

						} elseif ( isset( $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'attributes' ] ) && isset( $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'variation_id' ] ) && isset( $_GET[ 'order_again' ] ) ) {

							$value = sanitize_title( trim( stripslashes( $cart_item_data[ 'composite_data' ][ $bundled_item_id ][ 'attributes' ][ esc_html( $attribute[ 'name' ] ) ] ) ) );

							$valid_value = $variation_data[ $taxonomy ];

							if ( $valid_value == '' || $valid_value == $value ) {
								continue;
							}
						}

					    $all_set = false;
					}

					if ( ! $all_set ) {
    					$woocommerce->add_error( sprintf( __( 'Please choose &quot;%s&quot; options&hellip;', 'woocommerce-bto' ), $bto_data[ $bundled_item_id ][ 'title' ] ) );
						return false;
					}

				}
				elseif ( $bundled_product_type == 'simple' ) {

					if ( get_post_meta( $bundled_product_id, '_price', true ) === '' ) {
						$woocommerce->add_error( sprintf( __( 'Sorry, &quot;%s&quot; cannot be purchased.', 'woocommerce-bto' ), get_the_title( $bundled_product_id ) ) );
						return false;
					}

					$stock_check_data[ $bundled_product_id ][ 'type' ] = 'simple';

					if ( isset( $stock_check_data[ $bundled_product_id ][ 'quantity' ] ) )
						$stock_check_data[ $bundled_product_id ][ 'quantity' ] += $quantity;
					else {
						$stock_check_data[ $bundled_product_id ][ 'quantity' ] = $quantity;
					}

				}

				// Validate add-ons

				global $Product_Addon_Cart;

				if ( ! empty( $Product_Addon_Cart ) && version_compare( $Product_Addon_Cart->version, '2.1.3' ) > 0 ) {

					$this->addons_prefix = $bundled_item_id;

					if ( ! $Product_Addon_Cart->validate_add_cart_item( true, $bundled_product_id, $quantity ) )
						return false;

					$this->addons_prefix = '';
				}

				// Validate nyp

				global $Name_Your_Price_Cart;

				if ( get_post_meta( $product_id, '_per_product_pricing_active', true ) == 'yes' && ! empty( $Name_Your_Price_Cart ) ) {

					$this->nyp_prefix = $bundled_item_id;

					if ( ! $Name_Your_Price_Cart->validate_add_cart_item( true, $bundled_product_id, $quantity ) )
						return false;

					$this->nyp_prefix = '';
				}

			}

			// Check stock for bundled items one by one
			// If out of stock, don't proceed

			foreach ( $stock_check_data as $bundled_product_id => $data ) {

				if ( $data[ 'type' ] == 'variable' ) {

					foreach( $data['variations'] as $variation_id ) {

						if ( array_key_exists( $variation_id, $data[ 'managed_quantities' ] ) )
							$quantity = $data[ 'managed_quantities' ][ $variation_id ];
						else
							$quantity = $data[ 'quantity' ];

						if ( ! $this->validate_stock( $bundled_product_id, $variation_id, $quantity, false, false ) )
							return false;

					}

				}
				elseif ( $data[ 'type' ] == 'simple' ) {

					// if out of stock, don't proceed
					if ( ! $this->validate_stock( $bundled_product_id, '', $data[ 'quantity' ], false, false ) ) {
						return false;
					}

				}
			}

		}

		return $add;
	}

	/**
	 * Displays a warning message if version check fails.
	 * @return string
	 */
	function woo_bto_admin_notice() {
	    echo '<div class="error"><p>' . __( 'WooCommerce Composite Products requires at least WooCommerce 2.0.11 in order to function. Please upgrade WooCommerce.', 'woocommerce-bto') . '</p></div>';
	}


	/**
	 * Ajax and non-ajax function to display product summaries when a product selection is made
	 * In the ajax case, all js parameters are passed via json
	 * In the non-ajax case, they directly set the corresponding window variables
	 * @param  mixed 	$product_id
	 * @param  mixed 	$item_id
	 * @param  mixed 	$container_id
	 * @return string
	 */
	function woo_bto_show_product( $product_id = '', $item_id = '', $container_id = '' ) {

		// If it's an ajax call, get the posted arguments and check them
		if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'woo_bto_show_product' ) {

			if ( isset( $_POST[ 'product_id' ] ) && $_POST[ 'product_id' ] !== '' && isset( $_POST[ 'item_id' ] ) && !empty( $_POST[ 'item_id' ] ) && isset( $_POST[ 'container_id' ] ) && !empty( $_POST[ 'container_id' ] ) ) {

				$product_id 	= (int) $_POST[ 'product_id' ];
				$item_id 		= (int) $_POST[ 'item_id' ];
				$container_id 	= (int) $_POST[ 'container_id' ];
				$data 			= array();

			} else {
				echo 'error';
				die();
			}

			// Optional product - 'None' option given (validate? TODO)
			if ( $product_id == 0 ) {

				$data[ 'product_type' ] 					= 'none';
				$data[ 'price_data' ][ 'price' ] 			= 0;
				$data[ 'price_data' ][ 'regular_price' ] 	= 0;

				echo json_encode( array(
					'product_data'	=> $data
				) );
				die();
			}
		} elseif ( $product_id === '0' ) {
			?>
				<script type="text/javascript">
					window.bto_price_data[<?php echo $container_id; ?>]['prices'][<?php echo $item_id; ?>] 			= '0';
					window.bto_price_data[<?php echo $container_id; ?>]['regular_prices'][<?php echo $item_id; ?>] 	= '0';
					window.bto_type_data[<?php echo $container_id; ?>][<?php echo $item_id; ?>] 					= 'none';
				</script>
			<?php

			return true;
		}

		$product 	= get_product( $product_id );
		$bto_data 	= maybe_unserialize( get_post_meta( $container_id, '_bto_data', true ) );

		$per_product_pricing = get_post_meta( $container_id, '_per_product_pricing_bto', true );

		$quantity_min = $bto_data[ $item_id ][ 'quantity_min' ];
		$quantity_max = $bto_data[ $item_id ][ 'quantity_max' ];

		$discount = isset( $bto_data[ $item_id ][ 'discount' ] ) ? $bto_data[ $item_id ][ 'discount' ] : 0;

		$hide_product_title 		= isset( $bto_data[ $item_id ][ 'hide_product_title' ] ) ? $bto_data[$item_id][ 'hide_product_title' ] : 'no';
		$hide_product_description 	= isset( $bto_data[ $item_id ][ 'hide_product_description' ] ) ? $bto_data[$item_id][ 'hide_product_description' ] : 'no';
		$hide_product_thumbnail 	= isset( $bto_data[ $item_id ][ 'hide_product_thumbnail' ] ) ? $bto_data[$item_id][ 'hide_product_thumbnail' ] : 'no';

		ob_start();

		$this->woo_bto_add_show_product_filters( array( 'discount' => $discount, 'per_product_pricing' => $per_product_pricing ) );

		if ( $product->is_type( 'simple' ) ) {

			$data[ 'product_type' ] = 'simple';

			$product_regular_price 	= empty( $product->regular_price ) ? ( double ) $product->price : ( double ) $product->regular_price;
			$product_price 			= $product->get_price();

			woocommerce_get_template('single-product/summary/bto-product-summary-simple.php', array(
				'product' 					=> $product,
				'bundle_id' 				=> $container_id,
				'component_id'				=> $item_id,
				'quantity_min' 				=> $quantity_min,
				'quantity_max' 				=> $quantity_max,
				'per_product_pricing'		=> $per_product_pricing,
				'hide_product_title'		=> $hide_product_title,
				'hide_product_description'	=> $hide_product_description,
				'hide_product_thumbnail'	=> $hide_product_thumbnail
			), '', $this->woo_bto_plugin_path() . '/templates/' );

			$data[ 'price_data' ][ 'price' ] 			= $product_price;
			$data[ 'price_data' ][ 'regular_price' ] 	= $product_regular_price;

			if ( ! isset( $_POST[ 'action' ] ) || $_POST[ 'action' ] != 'woo_bto_show_product' || ( isset( $_POST[ 'add-product-to-cart' ][ $item_id ] ) && $_POST[ 'add-product-to-cart' ][ $item_id ] == $product_id ) ) {

				?>
				<script type="text/javascript">
					window.bto_price_data[<?php echo $container_id; ?>]['prices'][<?php echo $item_id; ?>] 			= <?php echo $data['price_data']['price']; ?>;
					window.bto_price_data[<?php echo $container_id; ?>]['regular_prices'][<?php echo $item_id; ?>] 	= <?php echo $data['price_data']['regular_price']; ?>;

					window.bto_type_data[<?php echo $container_id; ?>][<?php echo $item_id; ?>] = 'simple';
				</script>
				<?php

			}

		} elseif ( $product->is_type( 'variable' ) ) {

			$data[ 'product_variations' ] = $product->get_available_variations();

			$data[ 'product_type' ] = 'variable';

			foreach ( $data['product_variations'] as &$variation_data ) {

				$variation_data['min_qty'] = $quantity_min;
				$variation_data['max_qty'] = empty( $variation_data['max_qty'] ) ? $quantity_max : min( $variation_data['max_qty'], $quantity_max );
			}


			if ( ! isset( $_POST['action'] ) || $_POST['action'] != 'woo_bto_show_product' || ( isset( $_POST['variation_id'][ $item_id ] ) && $_POST['variation_id'][ $item_id ] == $product_id ) ) {

				?>
				<script type="text/javascript">

				if ( ! product_variations_backup )
					var product_variations_backup = new Array();

				if ( ! product_variations )
					var product_variations = new Array();

				product_variations_backup[<?php echo $item_id; ?>] 	= <?php echo json_encode( $data['product_variations'] ); ?>;
				//product_variations[<?php echo $item_id; ?>] 		= <?php echo json_encode( $data['product_variations'] ); ?>;

				window.bto_type_data[<?php echo $container_id; ?>][<?php echo $item_id; ?>] = 'variable';

				</script>
				<?php
			}

			woocommerce_get_template( 'single-product/summary/bto-product-summary-variable.php', array(
				'product' 					=> $product,
				'bundle_id' 				=> $container_id,
				'component_id'				=> $item_id,
				'item_id'					=> $item_id,
				'hide_product_title'		=> $hide_product_title,
				'hide_product_description'	=> $hide_product_description,
				'hide_product_thumbnail'	=> $hide_product_thumbnail
			), '', $this->woo_bto_plugin_path() . '/templates/' );
		}

		$this->woo_bto_remove_show_product_filters();

		$output = ob_get_clean();

		if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'woo_bto_show_product' ) {

			echo json_encode( array(
				'markup' 		=> $output,
				'product_data'	=> $data
			) );

			die();

		} else {
			echo $output;
		}

	}

	/**
	 * Filters variation data in the woo_bto_show_product function
	 * @param  mixed 					$variation_data
	 * @param  WC_Product 				$bundled_product
	 * @param  WC_Product_Variation 	$bundled_variation
	 * @return mixed
	 */
	function woo_bto_available_variation( $variation_data, $bundled_product, $bundled_variation ) {

		if ( ! empty ( $this->show_product_filter_params ) ) {

			// Add price info

			$regular_price 				= $bundled_variation->regular_price;
			$variation_regular_price 	= empty( $regular_price ) ? ( double ) $bundled_variation->price : ( double ) $regular_price;

			// Variations don't filter get_price_html() correctly, so this is necessary
			$bundled_variation->sale_price 	= $bundled_variation->get_price();
			$bundled_variation->price 		= $bundled_variation->get_price();

			$variation_data[ 'regular_price' ] 	= $variation_regular_price;
			$variation_data[ 'price' ]			= $bundled_variation->price;
			$variation_data[ 'price_html' ]		= $this->show_product_filter_params[ 'per_product_pricing' ] ? '<span class="price">' . $bundled_variation->get_price_html() . '</span>' : '';
		}

		return $variation_data;
	}

	function woo_bto_add_show_product_filters( $filter_params ) {

		$this->show_product_filter_params[ 'discount' ] 			= $filter_params[ 'discount' ];
		$this->show_product_filter_params[ 'per_product_pricing' ] 	= $filter_params[ 'per_product_pricing' ] == 'yes' ? true : false;

		add_filter( 'woocommerce_available_variation', array( $this, 'woo_bto_available_variation' ), 10, 3 );
		add_filter( 'woocommerce_get_price', array( $this, 'woo_bto_show_product_get_price' ), 100, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'woo_bto_show_product_get_price_html' ), 5, 2 );
	}

	function woo_bto_show_product_get_price_html( $price_html, $product ) {

		if ( ! empty ( $this->show_product_filter_params ) && ! isset( $product->is_filtered_price_html ) ) {

			if ( ! $this->show_product_filter_params[ 'per_product_pricing' ] )
				return '';

			$product->sale_price 	= $product->get_price();
			$product->price 		= $product->get_price();

			$product->is_filtered_price_html = 'yes';

			return $product->get_price_html();
		}

		return $price_html;
	}

	function woo_bto_show_product_get_price( $price, $product ) {

		if ( ! empty ( $this->show_product_filter_params ) ) {

			if ( ! $this->show_product_filter_params[ 'per_product_pricing' ] )
				return 0;

			$regular_price 	= $product->regular_price;
			$price 			= $product->price;

			$discount = $this->show_product_filter_params[ 'discount' ];

			return empty( $discount ) || empty( $regular_price ) ? $price : ( double ) $regular_price * ( 100 - $discount ) / 100;
		}

		return $price;
	}

	function woo_bto_remove_show_product_filters() {

		$this->show_product_filter_params = array();

		remove_filter( 'woocommerce_get_price', array( $this, 'woo_bto_show_product_get_price' ), 100, 2 );
		remove_filter( 'woocommerce_get_price_html', array( $this, 'woo_bto_show_product_get_price_html' ), 10, 2 );
		remove_filter( 'woocommerce_available_variation', array( $this, 'woo_bto_available_variation' ), 10, 3 );
	}

	/**
	 * Replaces add_to_cart button url with something more appropriate.
	 * @param  string $url
	 * @return string
	 */
	function woo_bto_add_to_cart_url( $url ) {

		global $product;

		if ( $product->is_type( 'bto' ) ) {
			return get_permalink( $product->id );
		}

		return $url;
	}

	/**
	 * Replaces add_to_cart text with something more appropriate.
	 * @param  string $text
	 * @return string
	 */
		function woo_bto_add_to_cart_text( $text ) {

		global $product;

		if ( $product->is_type( 'bto' ) ) {
			return __( 'Select Options', 'woocommerce-bto' );
		}

		return $text;
	}

	/**
	 * Add-to-cart template for composite products
	 * @return void
	 */
	function woo_bto_add_to_cart() {
		global $product, $post, $Name_Your_Price_Display;

		// Enqueue scripts and styles - then, initialize js variables
		wp_enqueue_script( 'wc-bto' );
		wp_enqueue_style( 'wc-bto-css' );

		// Load NYP scripts
		if ( ! empty( $Name_Your_Price_Display ) ) {
			$Name_Your_Price_Display->nyp_scripts();
		}

		$bto_data = $product->get_bto_data();
		?>

		<script type="text/javascript">

		if ( ! window['bto_style'] )
			var bto_style = new Array();

		bto_style[<?php echo $product->id; ?>] = '<?php echo $product->style; ?>';

		if ( ! window['bto_price_data'] )
			var bto_price_data = new Array();

		if ( ! window['bto_type_data'] ) {
			var bto_type_data = new Array();
			bto_type_data[ <?php echo $product->id; ?> ] = [];
		}

		if ( ! window['bto_nav_titles'] ) {
			var bto_nav_titles = new Array();
		}

		bto_style[<?php echo $product->id; ?>] 	= '<?php echo $product->style; ?>';
		bto_price_data[<?php echo $product->id; ?>] = <?php echo json_encode( $product->get_bto_price_data() ); ?>;

		</script>

		<?php

		$bto_data 	= $product->get_bto_data();
		$loop 		= 0;
		$steps 		= count( $bto_data );
		$added 		= false;

		if ( $product->style != 'paged' ) {

			foreach ( $bto_data as $group_id => $group_data ) {

				$loop++;

				woocommerce_get_template('single-product/bto-item.php', array(
					'group_id' 		=> $group_id,
					'group_data' 	=> $group_data,
					'step'			=> $loop,
					'steps'			=> $steps
				), '', $this->woo_bto_plugin_path() . '/templates/' );
			}

		} else {

			$added = true;

			foreach ( $bto_data as $group_id => $group_data ) {
				if ( ! isset( $_POST['add-product-to-cart'][ $group_id ] ) || $_POST['add-product-to-cart'][ $group_id ] === '' )
					$added = false;
			}

			foreach ( $bto_data as $group_id => $group_data ) {

				$loop++;

				woocommerce_get_template('single-product/bto-multipage-item.php', array(
					'group_id' 		=> $group_id,
					'group_data' 	=> $group_data,
					'step'			=> $loop,
					'steps'			=> $steps,
					'added'			=> $added
				), '', $this->woo_bto_plugin_path() . '/templates/' );
			}
		}

		woocommerce_get_template('single-product/add-to-cart/bto-cart-button.php', array(
			'product' 	=> $product,
			'added'		=> $added
		), '', $this->woo_bto_plugin_path() . '/templates/' );

	}

	/**
	 * Self explanatory
	 * @return void
	 */
	function woo_bto_admin_scripts() {

		wp_register_script( 'wc_bto_writepanel', $this->woo_bto_plugin_url() . '/assets/js/bto-product-write-panels.js', array('jquery', 'jquery-ui-datepicker', 'woocommerce_writepanel'), $this->version );

		wp_register_style( 'wc_bto_writepanel_css', $this->woo_bto_plugin_url() . '/assets/css/bto-write-panels.css', array('woocommerce_admin_styles'), $this->version );

		// Get admin screen id
		$screen = get_current_screen();

		// WooCommerce admin pages
		if (in_array( $screen->id, array( 'product' ))) {
			wp_enqueue_script( 'wc_bto_writepanel' );

			$bto_bundles_params = array(
				'select_products_label' 				=> __( 'Component Options', 'woocommerce-bto' ),
				'item_default_option_label' 			=> __( 'Default Option', 'woocommerce-bto' ),
				'item_default_option_save_warning' 		=> __( 'To configure this property, the Composite must be saved', 'woocommerce-bto' ),
				'item_title_label' 						=> __( 'Component Name', 'woocommerce-bto' ),
				'item_description_label' 				=> __( 'Component Description', 'woocommerce-bto' ),
				'item_quantity_min_label' 				=> __( 'Min Quantity', 'woocommerce-bto' ),
				'item_quantity_max_label' 				=> __( 'Max Quantity', 'woocommerce-bto' ),
				'item_discount_label' 					=> __( 'Discount %', 'woocommerce-bto' ),
				'item_optional_label' 					=> __( 'Optional', 'woocommerce-bto' ),
				'add_products_label' 					=> __( 'Search for a product&hellip;', 'woocommerce' ),
				'item_hide_product_title_label' 		=> __( 'Hide Product Title', 'woocommerce-bto' ),
				'item_hide_product_description_label' 	=> __( 'Hide Product Description', 'woocommerce-bto' ),
				'item_hide_product_thumbnail_label' 	=> __( 'Hide Product Thumbnail', 'woocommerce-bto' ),
				'component_configuration_title' 		=> __( 'Component Configuration', 'woocommerce-bto' ),
				'component_layout_title' 				=> __( 'Component Layout', 'woocommerce-bto' )
			);

			wp_localize_script( 'wc_bto_writepanel', 'bto_bundles_params', $bto_bundles_params );
		}

		if (in_array( $screen->id, array( 'edit-product', 'product' )))
			wp_enqueue_style( 'wc_bto_writepanel_css' );
	}

	function woo_bto_frontend_scripts() {
		wp_register_script( 'wc-bto', $this->woo_bto_plugin_url() . '/assets/js/add-to-cart-bto.js', array( 'jquery', 'jquery-blockui', 'wc-add-to-cart-variation' ), $this->version );
		wp_register_style( 'wc-bto-css', $this->woo_bto_plugin_url() . '/assets/css/bto-frontend.css', $this->version, false );
		wp_register_style( 'wc-bto-styles', $this->woo_bto_plugin_url() . '/assets/css/bto-styles.css', $this->version, false );
		wp_enqueue_style( 'wc-bto-styles' );
	}


	/**
	 * Process, verify and save composite product data
	 * @param  int 	$post_id
	 * @return void
	 */
	function woo_bto_process_bundle_meta( $post_id ) {

		global $woocommerce_errors, $woocommerce;

		// Composite Product Pricing

		$date_from = ( isset( $_POST['_sale_price_dates_from'] ) ) ? $_POST['_sale_price_dates_from'] : '';
		$date_to = ( isset( $_POST['_sale_price_dates_to'] ) ) ? $_POST['_sale_price_dates_to'] : '';

		// Dates
		if ($date_from) :
			update_post_meta( $post_id, '_sale_price_dates_from', strtotime( $date_from ) );
		else :
			update_post_meta( $post_id, '_sale_price_dates_from', '' );
		endif;

		if ($date_to) :
			update_post_meta( $post_id, '_sale_price_dates_to', strtotime( $date_to ) );
		else :
			update_post_meta( $post_id, '_sale_price_dates_to', '' );
		endif;

		if ($date_to && !$date_from) :
			update_post_meta( $post_id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );
		endif;

		// Update price if on sale
		if ( $_POST['_sale_price'] != '' && $date_to == '' && $date_from == '' ) :
			update_post_meta( $post_id, '_price', stripslashes( $_POST['_sale_price'] ) );
		else :
			update_post_meta( $post_id, '_price', stripslashes( $_POST['_regular_price'] ) );
		endif;

		if ( $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) :
			update_post_meta( $post_id, '_price', stripslashes($_POST['_sale_price']) );
		endif;

		if ($date_to && strtotime( $date_to ) < strtotime('NOW', current_time('timestamp'))) :
			update_post_meta( $post_id, '_price', stripslashes( $_POST['_regular_price'] ) );
			update_post_meta( $post_id, '_sale_price_dates_from', '');
			update_post_meta( $post_id, '_sale_price_dates_to', '');
		endif;


		// Per-Item Pricing

		if ( isset( $_POST['_per_product_pricing_bto'] ) ) {
			update_post_meta( $post_id, '_per_product_pricing_bto', 'yes' );
			delete_post_meta( $post_id, '_regular_price' );
			delete_post_meta( $post_id, '_sale_price' );
			delete_post_meta( $post_id, '_price' );
		} else {
			update_post_meta( $post_id, '_per_product_pricing_bto', 'no' );
			update_post_meta( $post_id, '_regular_price', stripslashes( $_POST['_regular_price'] ) );
			update_post_meta( $post_id, '_sale_price', stripslashes( $_POST['_sale_price'] ) );
		}



		// Shipping
		// Non-Bundled (per-item) Shipping

		if ( isset( $_POST['_per_product_shipping_bto'] ) ) {
			update_post_meta( $post_id, '_per_product_shipping_bto', 'yes' );
			update_post_meta( $post_id, '_virtual', 'yes' );
			update_post_meta( $post_id, '_weight', '' );
			update_post_meta( $post_id, '_length', '' );
			update_post_meta( $post_id, '_width', '' );
			update_post_meta( $post_id, '_height', '' );
		} else {
			update_post_meta( $post_id, '_per_product_shipping_bto', 'no' );
			update_post_meta( $post_id, '_virtual', 'no' );
			update_post_meta( $post_id, '_weight', stripslashes( $_POST['_weight'] ) );
			update_post_meta( $post_id, '_length', stripslashes( $_POST['_length'] ) );
			update_post_meta( $post_id, '_width', stripslashes( $_POST['_width'] ) );
			update_post_meta( $post_id, '_height', stripslashes( $_POST['_height'] ) );
		}

		// Composite style
		if ( isset( $_POST['bto_style'] ) )
			update_post_meta( $post_id, '_bto_style', stripslashes( $_POST['bto_style'] ) );
		else
			update_post_meta( $post_id, '_bto_style', 'single' );



		// Process Composite Product Configuration
		$bto_data = maybe_unserialize( get_post_meta( $post_id, '_bto_data', true ) );
		$zero_product_item_exists = false;

		if ( ! $bto_data )
			$bto_data = array();

		if ( isset( $_POST['bto_data'] ) ) {

			$counter = 0;
			$ordering = array();

			foreach ( $_POST['bto_data'] as $row_id => $post_data ) {

				$bto_ids = array_map( 'absint', $post_data['assigned_ids'] );

				if ( ! isset( $bto_ids ) || empty( $bto_ids ) ) {
					$zero_product_item_exists = true;
					continue;
				}

				$group_id = isset ( $post_data['group_id'] ) ? stripslashes( $post_data['group_id'] ) : ( current_time('timestamp') + $counter );
				$counter++;

				$bto_data[$group_id] = array();

				foreach ( $bto_ids as $key => $id ) {

					// Get product type
					$terms 			= get_the_terms( $id, 'product_type' );
					$product_type 	= ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

					if ( ( $id && $id > 0 ) && ( $product_type == 'simple' || $product_type == 'variable' ) && ( $post_id != $id ) ) {

						// Check that product exists

						if ( ! get_post( $id ) )
							continue;

						// Save assigned ids
						$bto_data[$group_id]['assigned_ids'][] = $id;
					}

				}

				// True if none of the ids exist
				if ( empty( $bto_data[$group_id]['assigned_ids'] ) ) {
					unset( $bto_data[$group_id] );
					$zero_product_item_exists = true;
					continue;
				}

				// Save default preferences - TODO Compat check
				if ( isset( $post_data['default_id'] ) && ! empty( $post_data['default_id'] ) ) {

					if ( $post_data['default_id'] == 'none' && isset( $post_data['optional'] ) )

						$bto_data[$group_id]['default_id'] = '0';

					elseif ( in_array( $post_data['default_id'], $bto_data[$group_id]['assigned_ids'] ) )

						$bto_data[$group_id]['default_id'] = stripslashes( $post_data['default_id'] );

					else {

						$bto_data[$group_id]['default_id'] = '';

						if ( !empty( $post_data['title'] ) )
							$woocommerce_errors[] = sprintf( __( 'The default option that you selected for \'%s\' is inconsistent with the set of active Component Options. Always double-check your preferences before saving, and always save any changes made to the Component Options before choosing new defaults.', 'woocommerce-bto' ), strip_tags( stripslashes( $post_data['title'] ) ) );
					}
				} else {

					// If the component option is only one, set it as default
					if ( count( $bto_data[$group_id]['assigned_ids'] ) == 1 )
						$bto_data[$group_id]['default_id'] = $bto_data[$group_id]['assigned_ids'][0];
					else
						$bto_data[$group_id]['default_id'] = '';
				}

				// Save title preferences
				if ( isset( $post_data['title'] ) && !empty( $post_data['title'] ) ) {
					$bto_data[$group_id]['title'] = strip_tags ( stripslashes( $post_data['title'] ) );
				} else {
					$bto_data[$group_id]['title'] = '';
					$woocommerce_errors[] = __( 'Please give a Name to all Components before publishing.', 'woocommerce-bto' );

					if ( isset( $_POST['post_status'] ) && $_POST['post_status'] == 'publish' ) {
						global $wpdb;
						$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
					}

				}

				// Save description preferences
				if ( isset( $post_data['description'] ) && !empty( $post_data['description'] ) ) {
					$bto_data[$group_id]['description'] = wp_kses_post( stripslashes( $post_data['description'] ) );
				} else {
					$bto_data[$group_id]['description'] = '';
				}

				// Save quantity data
				if ( isset( $post_data['quantity_min'] ) && isset( $post_data['quantity_max'] ) ) {

					if ( is_numeric( $post_data['quantity_min'] ) && is_numeric( $post_data['quantity_max'] ) ) {

						$quantity_min = ( int ) $post_data['quantity_min'];
						$quantity_max = ( int ) $post_data['quantity_max'];

						if ( $quantity_min > 0 && $quantity_max >= $quantity_min ) {

							$bto_data[$group_id]['quantity_min'] = $quantity_min;
							$bto_data[$group_id]['quantity_max'] = $quantity_max;

						} else {
							$woocommerce_errors[] = sprintf( __('The quantities you entered for \'%s\' were not valid and have been reset. Please enter positive integer values, with Quantity Min greater than or equal to Quantity Max.', 'woocommerce-bto'), strip_tags( stripslashes( $post_data['title'] ) ) );
							$bto_data[$group_id]['quantity_min'] = 1;
							$bto_data[$group_id]['quantity_max'] = 1;
						}
					}
				} else {
					// If its not there, it means the product was just added
					$bto_data[$group_id]['quantity_min'] = 1;
					$bto_data[$group_id]['quantity_max'] = 1;
				}

				// Save discount data
				if ( isset( $post_data['discount'] ) ) {

					if ( is_numeric( $post_data['discount'] ) ) {

						$discount = ( int ) $post_data['discount'];

						if ( $discount < 0 || $discount > 100 ) {
							$woocommerce_errors[] = sprintf( __('The discount value you entered for \'%s\' was not valid and has been reset. Please enter a positive integer between 0-100.', 'woocommerce-bto'), strip_tags( stripslashes( $post_data['title'] ) ) );
							$bto_data[$group_id]['discount'] = '';
						} else {
							$bto_data[$group_id]['discount'] = $discount;
						}
					} else {
						$bto_data[$group_id]['discount'] = '';
					}
				} else {
					$bto_data[$group_id]['discount'] = '';
				}

				// Save optional data
				if ( isset( $post_data['optional'] ) ) {
					$bto_data[$group_id]['optional'] = 'yes';
				} else {
					$bto_data[$group_id]['optional'] = 'no';
				}

				// Save hide product title data
				if ( isset( $post_data['hide_product_title'] ) ) {
					$bto_data[$group_id]['hide_product_title'] = 'yes';
				} else {
					$bto_data[$group_id]['hide_product_title'] = 'no';
				}

				// Save hide product description data
				if ( isset( $post_data['hide_product_description'] ) ) {
					$bto_data[$group_id]['hide_product_description'] = 'yes';
				} else {
					$bto_data[$group_id]['hide_product_description'] = 'no';
				}

				// Save hide product thumbnail data
				if ( isset( $post_data['hide_product_thumbnail'] ) ) {
					$bto_data[$group_id]['hide_product_thumbnail'] = 'yes';
				} else {
					$bto_data[$group_id]['hide_product_thumbnail'] = 'no';
				}

				// Save position data
				if ( isset( $post_data['position'] ) ) {
					$bto_data[$group_id]['position'] = (int) $post_data['position'];
					$ordering[(int) $post_data['position']] = $group_id;
				} else {
					$bto_data[$group_id]['position'] = -1;
					$ordering[count($ordering)] = $group_id;
				}

			}

			ksort( $ordering );
			$ordered_bto_data = array();

			foreach ( $ordering as $group_id ) {
			    $ordered_bto_data[$group_id] = $bto_data[$group_id];
			}

			// Save config
			update_post_meta( $post_id, '_bto_data', $ordered_bto_data );

			// Initialize and save price meta
			$composite = get_product( $post_id );

		}

		if ( ! isset( $_POST['bto_data'] ) || count( $bto_data ) == 0 ) {

			delete_post_meta( $post_id, '_bto_data' );

			$woocommerce_errors[] = __('Please define at least one Component for the Composite Product before publishing. To add a Component, click on the Composition tab and assign at least one product option to each Component.', 'woocommerce-bto');

			if ( isset( $_POST['post_status'] ) && $_POST['post_status'] == 'publish' ) {
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
			}

			return;
		}

		if ( $zero_product_item_exists ) {
			$woocommerce_errors[] = __('Please assign at least one product option to every Component. Products can be assigned to Components by clicking on the Component Options field.', 'woocommerce-bto' );
		}

	}

	/**
	 * Adds the 'composite product' type to the menu
	 * @param  array 	$options
	 * @return array
	 */
	function woo_bto_product_selector_filter( $options ) {
		$options['bto'] = __('Composite product', 'woocommerce-bto');
		return $options;
	}

	/**
	 * Adds the Composite Product write panel tabs
	 * @return string
	 */
	function woo_bto_product_write_panel_tab() {
		echo '<li class="bto_product_tab show_if_bto linked_product_options composite_product_options"><a href="#bto_product_data">'.__('Composition', 'woocommerce-bto').'</a></li>';
	}

	/**
	 * Product options for post-1.6.2 product data section
	 * @param  array $options
	 * @return array
	 */
	function woo_bto_type_options( $options ) {

		$options['per_product_shipping_bto'] = array(
			'id' => '_per_product_shipping_bto',
			'wrapper_class' => 'show_if_bto',
			'label' => __('Non-Bundled Shipping', 'woocommerce-bto'),
			'description' => __('If your Composite product consists of items that are assembled or packaged together, leave the box un-checked and just define the shipping properties of the product below. If, however, the composited items are shipped individually, their shipping properties must be retained. In this case, the box must be checked. \'Non-Bundled Shipping\' should also be selected when the composited items are all virtual.', 'woocommerce-bto')
		);

		$options['per_product_pricing_bto'] = array(
			'id' => '_per_product_pricing_bto',
			'wrapper_class' => 'show_if_bto bto_pricing',
			'label' => __('Per-Item Pricing', 'woocommerce-bto'),
			'description' => __('When enabled, the Composite product is priced per-item, based on the prices of the selected items.', 'woocommerce-bto')
		);

		return $options;
	}

	/**
	 * Write panel
	 * @return void
	 */
	function woo_bto_product_write_panel() {
		global $woocommerce, $post, $wpdb;

		$bto_data = maybe_unserialize( get_post_meta( $post->ID, '_bto_data', true ) );

		?>
		<div id="bto_product_data" class="bto_panel panel woocommerce_options_panel wc-metaboxes-wrapper">

			<div class="options_group bundle_group">
				<p class="form-field">
					<label><?php _e('Front-end Style', 'woocommerce-bto'); echo '<img class="help_tip" data-tip="' . __( '<strong>Single Page</strong>: Composite Components are presented in a list view. Component options can be selected in any sequence.</br><strong>Multi Page</strong>: Composite Components are presented in a paged view. Component options can only be chosen in sequence. A Review pane is displayed before adding the Composite to the cart.', 'woocommerce-bto' ) .'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" />'; ?></label>
					<select name="bto_style">
						<?php
						$style = get_post_meta( $post->ID, '_bto_style', true );
						echo '<option '. selected( $style, 'single', false ) .' value="single">' . __( 'Single-Page', 'woocommerce-bto' ) . '</option>';
						echo '<option '. selected( $style, 'paged', false ) .' value="paged">' . __( 'Multi-Page', 'woocommerce-bto' ) . '</option>';
						?>
					</select>
				</p>

			</div>
			<div class="options_group config_group">

				<p class="toolbar">
					<?php _e('Composite Components', 'woocommerce-bto'); echo '<img class="help_tip" data-tip="' . __( 'Composite Products are defined by <strong>Components</strong>, similar to how Variable Products are defined by Attributes. Every Composite Component can be mapped to a group of existing Simple or Variable Products - the <strong>Component Options</strong>.</br></br> The <strong>Quantity</strong> of every Component may vary between a Minimum and Maximum value, while Components may also be marked as <strong>Optional</strong>.</br></br><strong>Price Discounts</strong> may also be defined per-Compoenent, and are applied over regular prices: When defined, a Component Discount will override all defined sales prices.', 'woocommerce-bto' ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />'; ?>
					<a href="#" class="close_all"><?php _e('Close all', 'woocommerce'); ?></a>
					<a href="#" class="expand_all"><?php _e('Expand all', 'woocommerce'); ?></a>
				</p>

				<div class="bto_groups wc-metaboxes">

					<?php

					if ( $bto_data ) {

						$i = 0;

						foreach( $bto_data as $group_id => $data ) {

							echo '<div class="bto_group wc-metabox closed" rel="' . $data['position'] . '">
								<h3>
									<button type="button" class="remove_row button">' . __('Remove', 'woocommerce') . '</button>
									<div class="handlediv" title="' . __('Click to toggle', 'woocommerce') . '"></div>
									<strong class="group_name">' . $data['title'] . '</strong>
								</h3>
								<div class="bto_group_data wc-metabox-content">
									<div class="options_group">
										<h4>' . __('Component Configuration', 'woocommerce-bto') . '</h4>
										<div class="group_title">
											<p class="form-field">
												<label>' . __('Component Name', 'woocommerce-bto') . ':</label>
												<input type="text" class="group_title" name="bto_data[' . $i . '][title]" value="' . $data['title'] . '"/>
												<input type="hidden" name="bto_data[' . $i . '][position]" class="group_position" value="' . $data['position'] . '" />
												<input type="hidden" name="bto_data[' . $i . '][group_id]" class="group_id" value="' . $group_id . '" />
											</p>
										</div>
										<div class="group_description">
											<p class="form-field">
												<label>' . __('Component Description', 'woocommerce-bto') . ':</label>
												<textarea class="group_description" name="bto_data[' . $i . '][description]" id="group_description_' . $i . '" placeholder="" rows="2" cols="20">' . esc_html( $data['description'] ) . '</textarea>
											</p>
										</div>
										<div class="bto_selector">
											<p class="form-field">
												<label>' . __( 'Component Options', 'woocommerce-bto' ) . ':</label>
												<select id="bto_ids_' . $i . '" name="bto_data[' . $i . '][assigned_ids][]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="'.  __( 'Search for a product&hellip;', 'woocommerce' ) . '">';

										$item_ids = $data['assigned_ids'];

										if ( $item_ids ) {
											foreach ( $item_ids as $item_id ) {

												$title 	= get_the_title( $item_id );
												$sku 	= get_post_meta( $item_id, '_sku', true );

												if ( ! $title ) continue;

												if ( isset( $sku ) && $sku ) $sku = ' (SKU: ' . $sku . ')';
												echo '<option value="' . $item_id . '" selected="selected">'. $title . $sku . '</option>';
											}
										}

										echo '</select>
											</p>
										</div>
										<div class="default_selector">
											<p class="form-field">
												<label>' . __( 'Default Option', 'woocommerce-bto' ) . ':</label>
												<select id="group_default_' . $i . '" name="bto_data[' . $i . '][default_id]">
													<option value="">' . __('No default option', 'woocommerce-bto') . '&hellip;</option>';

													$selected_default = $data['default_id'];

													if ( $data['optional'] == 'yes' )
														echo '<option value="none" ' . selected( $selected_default, '0', false ) . '>'. __( 'None', 'woocommerce-bto' ) . '</option>';

													if ( $item_ids ) {
														foreach ( $item_ids as $item_id ) {

															$title 	= get_the_title( $item_id );
															$sku 	= get_post_meta( $item_id, '_sku', true );

															if ( ! $title ) continue;

															if ( isset( $sku ) && $sku ) $sku = ' (SKU: ' . $sku . ')';
															echo '<option value="' . $item_id . '" ' . selected( $selected_default, $item_id, false ) . '>'. $title . $sku . '</option>';
														}
													}

												echo '</select>
											</p>
										</div>
										<div class="group_quantity_min">
											<p class="form-field">
												<label for="group_quantity_min_' . $i . '">' . __( 'Min Quantity', 'woocommerce-bto' ) . ':</label>
												<input type="number" class="group_quantity_min" name="bto_data[' . $i . '][quantity_min]"\ id="group_quantity_min_' . $i . '" value="' . $data['quantity_min'] . '" placeholder="" step="1" min="1">
											</p>
										</div>
										<div class="group_quantity_max">
											<p class="form-field">
												<label for="group_quantity_max_' . $i . '">' . __( 'Max Quantity', 'woocommerce-bto' ) . ':</label>
												<input type="number" class="group_quantity_max" name="bto_data[' . $i . '][quantity_max]"\ id="group_quantity_max_' . $i . '" value="' . $data['quantity_max'] . '" placeholder="" step="1" min="1">
											</p>
										</div>
										<div class="group_discount">
											<p class="form-field">
												<label for="group_discount_' . $i . '">' . __( 'Discount %', 'woocommerce-bto' ) . ':</label>
												<input type="number" class="group_discount" name="bto_data[' . $i . '][discount]"\ id="group_discount_' . $i . '" value="' . $data['discount'] . '" placeholder="" step="any" min="0" max="100">
											</p>
										</div>
										<div class="group_optional" >
											<p class="form-field">
												<label for="group_optional_' . $i . '">' . __( 'Optional', 'woocommerce-bto' ) . ':</label>
												<input type="checkbox" class="checkbox"' . ( $data['optional'] == 'yes' ? ' checked="checked"' : '' ) . ' name="bto_data[' . $i . '][optional]' . ( $data['optional'] == 'yes' ? ' value="1"' : '' ) . '" />
											</p>
										</div>
									</div>
									<div class="options_group">
										<h4>' . __('Component Layout', 'woocommerce-bto') . '</h4>
										<div class="group_hide_product_title" >
											<p class="form-field">
												<label for="group_hide_product_title_' . $i . '">' . __( 'Hide Product Title', 'woocommerce-bto' ) . ':</label>
												<input type="checkbox" class="checkbox"' . ( $data['hide_product_title'] == 'yes' ? ' checked="checked"' : '' ) . ' name="bto_data[' . $i . '][hide_product_title]' . ( $data['hide_product_title'] == 'yes' ? ' value="1"' : '' ) . '" />
											</p>
										</div>
										<div class="group_hide_product_description" >
											<p class="form-field">
												<label for="group_hide_product_description_' . $i . '">' . __( 'Hide Product Description', 'woocommerce-bto' ) . ':</label>
												<input type="checkbox" class="checkbox"' . ( $data['hide_product_description'] == 'yes' ? ' checked="checked"' : '' ) . ' name="bto_data[' . $i . '][hide_product_description]' . ( $data['hide_product_description'] == 'yes' ? ' value="1"' : '' ) . '" />
											</p>
										</div>
										<div class="group_hide_product_thumbnail" >
											<p class="form-field">
												<label for="group_hide_product_thumbnail_' . $i . '">' . __( 'Hide Product Thumbnail', 'woocommerce-bto' ) . ':</label>
												<input type="checkbox" class="checkbox"' . ( $data['hide_product_thumbnail'] == 'yes' ? ' checked="checked"' : '' ) . ' name="bto_data[' . $i . '][hide_product_thumbnail]' . ( $data['hide_product_thumbnail'] == 'yes' ? ' value="1"' : '' ) . '" />
											</p>
										</div>
									</div>
								</div>
							</div>';

							$i++;
						}
					}

					?>
				</div>

				<p class="toolbar">
					<button type="button" class="button button-primary add_bto_group"><?php _e( 'Add Component', 'woocommerce-bto' ); ?></button>
				</p>

			</div> <!-- options group -->

		</div>
		<?php
	}


}

$GLOBALS['woocommerce_bto'] = new WC_BTO();

