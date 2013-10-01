<?php
/**
 * Composite Product Class
 * @class 	WC_Product_Bto
 * @version 1.5.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Product_Bto extends WC_Product {

	var $bto_data = array();
	var $per_product_pricing;
	var $per_product_shipping;

	var $style;

	var $min_composite_price;
	var $max_composite_price;
	var $min_composite_regular_price;
	var $max_composite_regular_price;

	var $bto_price_data = array();
	var $contains_nyp;

	function __construct( $bundle_id ) {

		$this->product_type = 'bto';

		parent::__construct( $bundle_id );

		$this->bto_data 			= maybe_unserialize( get_post_meta( $this->id, '_bto_data', true ) );
		$this->per_product_pricing 	= get_post_meta( $this->id, '_per_product_pricing_bto', true );
		$this->per_product_shipping = get_post_meta( $this->id, '_per_product_shipping_bto', true );
		$this->style 				= get_post_meta( $this->id, '_bto_style', true );

		$this->contains_nyp = false;

		if ( $this->bto_data ) {
			$this->initialize();
		}
	}


	function get_bto_data() {
		return $this->bto_data;
	}


	function initialize() {

		$this->init_price_data();

		// initialize min/max price information
		$this->min_composite_price = $this->max_composite_price = $this->min_composite_regular_price = $this->max_composite_regular_price = '';

		if ( $this->per_product_pricing == 'yes' ) {

			foreach ( $this->bto_data as $group_id => $group_data ) {

				$item_min_price = '';
				$item_max_price = '';

				$item_min_regular_price = '';
				$item_max_regular_price = '';

				if ( $group_data[ 'optional' ] == 'yes' )
					continue;

				foreach ( $group_data[ 'assigned_ids' ] as $id ) {

					if ( get_post_status( $id ) != 'publish' )
						continue;

					// Get product type
					$terms 			= get_the_terms( $id, 'product_type' );
					$product_type 	= ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';


					if ( $product_type == 'simple' ) {

						$product_price = get_post_meta( $id, '_price', true );

						if ( $product_price === '' )
							continue;

						$regular_price = get_post_meta( $id, '_regular_price', true );
						$product_regular_price = empty( $regular_price ) ? ( double ) $product_price : ( double ) $regular_price;

						$product_price = empty( $group_data[ 'discount' ] ) || empty( $regular_price ) ? ( double ) $product_price : $product_regular_price * ( 100 - $group_data[ 'discount' ] ) / 100;

						// Name your price support
						if ( class_exists( 'Name_Your_Price_Helpers' ) && Name_Your_Price_Helpers::is_nyp( $id ) ) {

							$product_price = $product_regular_price = Name_Your_Price_Helpers::get_minimum_price( $id ) ? Name_Your_Price_Helpers::get_minimum_price( $id ) : 0;

							$this->contains_nyp = true;
						}

						$item_min_price = $item_min_price !== '' ? min( $item_min_price, $product_price ) : $product_price;
						$item_max_price = $item_max_price !== '' ? max( $item_max_price, $product_price ) : $product_price;

						$item_min_regular_price = $item_min_regular_price !== '' ? min( $item_min_regular_price, $product_regular_price ) : $product_regular_price;
						$item_max_regular_price = $item_max_regular_price !== '' ? max( $item_max_regular_price, $product_regular_price ) : $product_regular_price;


					} elseif ( $product_type == 'variable' ) {

						$min_variation_regular_price 	= get_post_meta( $id, '_min_variation_regular_price', true );
						$min_variation_sale_price 		= get_post_meta( $id, '_min_variation_sale_price', true );
						$max_variation_regular_price 	= get_post_meta( $id, '_max_variation_regular_price', true );
						$max_variation_sale_price 		= get_post_meta( $id, '_max_variation_sale_price', true );

						$min_variation_sale_price 	= empty( $group_data[ 'discount' ] ) ? $min_variation_sale_price : ( double ) $min_variation_regular_price * ( 100 - $group_data[ 'discount' ] ) / 100;
						$max_variation_sale_price 	= empty( $group_data[ 'discount' ] ) ? $max_variation_sale_price : ( double ) $max_variation_regular_price * ( 100 - $group_data[ 'discount' ] ) / 100;

						$product_min_price 		= ( $min_variation_sale_price === '' || $min_variation_regular_price < $min_variation_sale_price ) ? $min_variation_regular_price : $min_variation_sale_price;
						$item_min_price 		= $item_min_price !== '' ? min( $item_min_price, $product_min_price ) : $product_min_price;
						$item_min_regular_price = $item_min_regular_price !== '' ? min( $item_min_regular_price, $min_variation_regular_price ) : $min_variation_regular_price;

						$product_max_price 		= ( $max_variation_sale_price === '' || $max_variation_regular_price < $max_variation_sale_price ) ? $max_variation_regular_price : $max_variation_sale_price;
						$item_max_price 		= $item_max_price !== '' ? max( $item_max_price, $product_max_price ) : $product_max_price;
						$item_max_regular_price = $item_max_regular_price !== '' ? max( $item_max_regular_price, $max_variation_regular_price ) : $max_variation_regular_price;
					}

				}

				$this->min_composite_price 			= $this->min_composite_price + $group_data[ 'quantity_min' ] * $item_min_price;
				$this->min_composite_regular_price 	= $this->min_composite_regular_price + $group_data[ 'quantity_min' ] * $item_min_regular_price;
				$this->max_composite_price 			= $this->max_composite_price + $group_data[ 'quantity_max' ] * $item_max_price;
				$this->max_composite_regular_price 	= $this->max_composite_regular_price + $group_data[ 'quantity_max' ] * $item_max_regular_price;

			}

			// Saved price is the one of the cheapest combination
			if ( $this->price != $this->min_composite_price )
				update_post_meta( $this->id, '_price', $this->min_composite_price );

			$this->price = 0;
		}

	}


	function init_price_data() {

		$this->bto_price_data[ 'currency_symbol' ] 					= get_woocommerce_currency_symbol();
		$this->bto_price_data[ 'woocommerce_price_num_decimals' ] 	= (int) get_option( 'woocommerce_price_num_decimals' );
		$this->bto_price_data[ 'woocommerce_currency_pos' ] 		= get_option( 'woocommerce_currency_pos' );
		$this->bto_price_data[ 'woocommerce_price_decimal_sep' ] 	= stripslashes( get_option( 'woocommerce_price_decimal_sep' ) );
		$this->bto_price_data[ 'woocommerce_price_thousand_sep' ] 	= stripslashes( get_option( 'woocommerce_price_thousand_sep' ) );
		$this->bto_price_data[ 'woocommerce_price_trim_zeros' ] 	= get_option( 'woocommerce_price_trim_zeros' );

		$this->bto_price_data[ 'free' ] = __( 'Free!', 'woocommerce' );

		$this->bto_price_data[ 'per_product_pricing' ] = $this->per_product_pricing == 'yes' ? true : false;

		$this->bto_price_data[ 'prices' ] 		= array();
		$this->bto_price_data[ 'regular_prices' ] = array();

		$this->bto_price_data[ 'total' ] 			= ( ( $this->per_product_pricing == 'yes' ) ? (float) 0 : (float) ( $this->get_price() == '' ? -1 : $this->get_price() ) );
		$this->bto_price_data[ 'regular_total' ] 	= ( ( $this->per_product_pricing == 'yes' ) ? (float) 0 : (float) $this->regular_price );

		$this->bto_price_data[ 'total_description' ] = __( 'Total', 'woocommerce-bto' ) . ': ';
	}


	function get_bto_price_data() {
		return $this->bto_price_data;
	}


	function get_price_html( $price = '' ) {

		if ( $this->per_product_pricing == 'yes' ) {

			// Get the price
			if ( $this->min_composite_price > 0 ) :
				if ( $this->min_composite_regular_price !== $this->min_composite_price ) :

					if ( ! $this->min_composite_price || $this->min_composite_price !== $this->max_composite_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_composite_regular_price, $this->min_composite_price );

					$price = apply_filters( 'woocommerce_composite_sale_price_html', $price, $this );

				else :

					if ( ! $this->min_composite_price || $this->min_composite_price !== $this->max_composite_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= woocommerce_price( $this->min_composite_price );

					$price = apply_filters( 'woocommerce_composite_price_html', $price, $this );

				endif;
			elseif ( $this->min_composite_price === '' ) :

				$price = apply_filters( 'woocommerce_composite_empty_price_html', '', $this );

			elseif ( $this->min_composite_price == 0 ) :

				if ( $this->is_on_sale() && isset( $this->min_composite_regular_price ) && $this->min_composite_regular_price !== $this->min_composite_price ) :

					if ( ! $this->min_composite_price || $this->min_composite_price !== $this->max_composite_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_composite_regular_price, __( 'Free!', 'woocommerce' ) );

					$price = apply_filters( 'woocommerce_composite_free_sale_price_html', $price, $this );

				else :

					if ( !$this->min_composite_price || $this->min_composite_price !== $this->max_composite_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= __( 'Free!', 'woocommerce' );

					$price = apply_filters( 'woocommerce_composite_free_price_html', $price, $this );

				endif;

			endif;

		} else {

			if ( $this->price > 0 ) :
				if ( $this->is_on_sale() && isset( $this->regular_price ) ) :

					$price .= $this->get_price_html_from_to( $this->regular_price, $this->get_price() );

					$price = apply_filters( 'woocommerce_sale_price_html', $price, $this );

				else :

					$price .= woocommerce_price( $this->get_price() );

					$price = apply_filters( 'woocommerce_price_html', $price, $this );

				endif;
			elseif ( $this->price === '' ) :

				$price = apply_filters( 'woocommerce_empty_price_html', '', $this );

			elseif ( $this->price == 0 ) :

				if ( $this->is_on_sale() && isset( $this->regular_price ) ) :

					$price .= $this->get_price_html_from_to( $this->regular_price, __( 'Free!', 'woocommerce' ) );

					$price = apply_filters( 'woocommerce_free_sale_price_html', $price, $this );

				else :

					$price = __( 'Free!', 'woocommerce' );

					$price = apply_filters( 'woocommerce_free_price_html', $price, $this );

				endif;

			endif;
		}

		return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}

	function contains_nyp() {

		if ( $this->contains_nyp )
			return true;
		else
			return false;
	}

}

