<?php
/**
 * Product Bundle Class
 *
 * @class 	WC_Product_Bundle
 * @version 3.5.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class WC_Product_Bundle extends WC_Product {

	var $bundled_item_ids;
	var $bundled_products;

	var $min_bundle_price;
	var $max_bundle_price;
	var $min_bundle_regular_price;
	var $max_bundle_regular_price;

	var $bundle_attributes;
	var $available_bundle_variations;
	var $selected_bundle_attributes;

	var $allowed_variations;
	var $variation_filters_active = array();
	var $has_filters;

	var $filtered_variation_attributes = array();

	var $bundle_defaults;
	var $bundle_defaults_active = array();
	var $has_overrides;

	var $bundled_item_quantities = array();
	var $bundled_item_discounts = array();

	var $per_product_pricing_active;
	var $per_product_shipping_active;

	var $all_items_sold_individually;
	var $all_items_in_stock;

	var $on_sale;

	var $bundle_price_data;

	var $contains_nyp;

	var $processing_item_id;

	var $item_id_for_price_filter;

	var $enable_bundle_transients;

	function __construct( $bundle_id ) {

		global $woocommerce_bundles;

		$this->product_type = 'bundle';

		parent::__construct( $bundle_id );

		$this->bundled_item_ids = maybe_unserialize( get_post_meta( $this->id, '_bundled_ids', true ) );

		$this->has_filters 		= false;
		$this->has_overrides 	= false;

		$this->contains_nyp = false;
		$this->on_sale 		= false;

		$this->all_items_sold_individually 	= true;
		$this->all_items_in_stock			= true;

		if ( $this->bundled_item_ids ) {
			foreach( $this->bundled_item_ids as $bundled_id ) {

				// Store 'variation filtering' boolean variables
				if ( get_post_meta( $this->id, 'filter_variations_'.$bundled_id, 'true' ) == 'yes' ) {
					$this->variation_filters_active[ $bundled_id ] = true;
					$this->has_filters = true;
				} else {
					$this->variation_filters_active[ $bundled_id ] = false;
				}

				// Store 'override defaults' boolean variables
				if ( get_post_meta( $this->id, 'override_defaults_'.$bundled_id, 'true' ) == 'yes' ) {
					$this->bundle_defaults_active[ $bundled_id ] = true;
					$this->has_overrides = true;
				} else {
					$this->bundle_defaults_active[ $bundled_id ] = false;
				}

				// Store bundled item quantities
				$this->bundled_item_quantities[ $bundled_id ] = (int) get_post_meta( $this->id, 'bundle_quantity_' . $bundled_id, true );

				// Store bundled item discounts
				$this->bundled_item_discounts[ $bundled_id ] = get_post_meta( $this->id, 'bundle_discount_' . $bundled_id, true );
			}
		}

		if ( $this->has_filters ) {
			$this->allowed_variations = maybe_unserialize( get_post_meta( $this->id, '_allowed_variations', true ) );

			// create array of attributes based on active variations

			foreach ( $this->allowed_variations as $item_id => $allowed_variations ) {

				if ( ! $this->variation_filters_active[ $item_id ] )
					continue;

				$sep = explode( '_', $item_id );
				$product_id = $sep[0];

				$attributes = ( array ) maybe_unserialize( get_post_meta( $product_id, '_product_attributes', true ) );

				// filtered variation attributes (stores attributes of active variations)
				$filtered_attributes = array();

				$this->filtered_variation_attributes[$item_id] = array();

				// make array of active variation attributes
				foreach ( $this->allowed_variations[$item_id] as $allowed_variation_id ) {

					$description = '';

					$variation_data = get_post_meta( $allowed_variation_id );

					foreach ( $attributes as $attribute ) {

						// Only deal with attributes that are variations
						if ( ! $attribute[ 'is_variation' ] )
							continue;

						// Get current value for variation (if set)
						$variation_selected_value = isset( $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] ) ? $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] : '';

						// Get terms for attribute taxonomy or value if its a custom attribute
						if ( $attribute[ 'is_taxonomy' ] ) {

							$post_terms = wp_get_post_terms( $product_id, $attribute[ 'name' ] );

							foreach ( $post_terms as $term ) {

								if ( $variation_selected_value == $term->slug || $variation_selected_value == '' ) {

									if( $variation_selected_value == '' )
										$description = 'Any';
									else
										$description = $term->name;

									if ( !isset( $filtered_attributes[ $attribute[ 'name' ] ] ) ) {

										$filtered_attributes[ $attribute[ 'name' ] ][ 'descriptions' ][] 	= $description;
										$filtered_attributes[ $attribute[ 'name'] ]['slugs' ][] 			= sanitize_title( $description );

									} elseif ( !in_array( $description, $filtered_attributes[ $attribute[ 'name' ] ][ 'descriptions' ] ) ) {

										$filtered_attributes[ $attribute[ 'name' ] ][ 'descriptions' ][] 	= $description;
										$filtered_attributes[ $attribute[ 'name' ] ][ 'slugs' ][] 			= sanitize_title( $description );
									}
								}

							}

						} else {

							$options = array_map( 'trim', explode( '|', $attribute[ 'value' ] ) );

							foreach ( $options as $option ) {

								if ( sanitize_title( $variation_selected_value ) == sanitize_title( $option ) || $variation_selected_value == '' ) {

									if( $variation_selected_value == '' )
										$description = 'Any';
									else
										$description = $option;

									if ( !isset( $filtered_attributes[ $attribute['name'] ] ) ) {

										$filtered_attributes[ $attribute[ 'name' ] ][ 'descriptions' ][] 	= $description;
										$filtered_attributes[ $attribute[ 'name' ] ][ 'slugs' ][] 			= sanitize_title( $description );

									} elseif ( !in_array( $description, $filtered_attributes[ $attribute[ 'name' ] ][ 'descriptions' ] ) ) {

										$filtered_attributes[ $attribute[ 'name' ] ][ 'descriptions' ][] 	= $description;
										$filtered_attributes[ $attribute[ 'name' ] ][ 'slugs' ][] 			= sanitize_title( $description );
									}
								}

							}

						}

					}


					// clean up product attributes
			        foreach ( $attributes as $attribute ) {

			            if ( ! $attribute[ 'is_variation' ] )
			            	continue;

						if ( array_key_exists( $attribute[ 'name' ], $filtered_attributes ) && !in_array( 'Any', $filtered_attributes[ $attribute[ 'name' ] ][ 'descriptions' ] ) )
							$this->filtered_variation_attributes[ $item_id ][ $attribute[ 'name' ] ] = $filtered_attributes[ $attribute[ 'name' ] ];
					}

				}

			}

		}

		if ( $this->has_overrides )
			$this->bundle_defaults = get_post_meta( $this->id, '_bundle_defaults', true );

		$this->per_product_pricing_active 	= ( get_post_meta( $this->id, '_per_product_pricing_active', true ) == 'yes' ) ? true : false;
		$this->per_product_shipping_active 	= ( get_post_meta( $this->id, '_per_product_shipping_active', true ) == 'yes' ) ? true : false;

		$this->enable_bundle_transients = get_post_meta( $this->id, 'enable_bundle_transients', true ) == 'yes' ? true : false;

		if ( $this->bundled_item_ids ) {
			$this->load_bundle_data();
		}

	}


	function load_bundle_data() {

		global $woocommerce_bundles;

		// stores bundle pricing strategy info and price table
		$this->bundle_price_data = array();

		$this->bundle_price_data['currency_symbol'] = get_woocommerce_currency_symbol();
		$this->bundle_price_data['woocommerce_price_num_decimals'] = ( int ) get_option('woocommerce_price_num_decimals');
		$this->bundle_price_data['woocommerce_currency_pos'] = get_option('woocommerce_currency_pos');
		$this->bundle_price_data['woocommerce_price_decimal_sep'] = stripslashes(get_option('woocommerce_price_decimal_sep'));
		$this->bundle_price_data['woocommerce_price_thousand_sep'] = stripslashes(get_option('woocommerce_price_thousand_sep'));
		$this->bundle_price_data['woocommerce_price_trim_zeros'] = get_option('woocommerce_price_trim_zeros');

		$this->bundle_price_data['total_description'] 	= __( 'Total', 'woo-bundles' ) . ': ';
		$this->bundle_price_data['free'] 				= __( 'Free!', 'woocommerce' );

		$this->bundle_price_data['per_product_pricing'] = $this->per_product_pricing_active;
		$this->bundle_price_data['prices'] = array();
		$this->bundle_price_data['regular_prices'] = array();
		$this->bundle_price_data['total'] = ( ($this->per_product_pricing_active) ? ( float ) 0 : ( float ) ( $this->get_price()=='' ? -1 : $this->get_price() ) );
		$this->bundle_price_data['regular_total'] = ( ($this->per_product_pricing_active) ? ( float ) 0 : ( float ) $this->regular_price );

		$this->bundle_attributes = array();
		$this->available_bundle_variations = array();
		$this->selected_bundle_attributes = array();
		$this->bundled_products = array();

		foreach ( $this->bundled_item_ids as $bundled_item_id ) {

			$this->processing_item_id = $bundled_item_id;

			// remove suffix
			$sep = explode( '_', $bundled_item_id );
			$product_id = $sep[0];

			if ( get_post_status( $product_id ) != 'publish' )
				continue;

			$bundled_product = get_product( $product_id );

			$this->bundled_products[ $bundled_item_id ] = $bundled_product;

			if ( $bundled_product->product_type == 'simple' ) {

				if ( $bundled_product->get_price() === '' )
					continue;

				if ( ! $bundled_product->is_sold_individually() )
					$this->all_items_sold_individually = false;

				if ( $this->all_items_in_stock && ( ! $bundled_product->is_in_stock() || ! $bundled_product->has_enough_stock( $this->bundled_item_quantities[ $bundled_item_id ] ) ) ) {
					$this->all_items_in_stock = false;
				}

				$discount 		= $this->bundled_item_discounts[ $bundled_item_id ];
				$price 			= $bundled_product->get_price();
				$regular_price 	= $bundled_product->regular_price;

				$product_regular_price 	= empty( $regular_price ) ? ( double ) $price : ( double ) $regular_price;
				$bundled_product_price 	= empty( $discount ) || empty( $regular_price ) ? ( double ) $price : $product_regular_price * ( 100 - $discount ) / 100;

				// Name your price support
				if ( class_exists( 'Name_Your_Price_Helpers' ) && Name_Your_Price_Helpers::is_nyp( $product_id ) ) {

					$bundled_product_price = $product_regular_price = Name_Your_Price_Helpers::get_minimum_price( $product_id ) ? Name_Your_Price_Helpers::get_minimum_price( $product_id ) : 0;

					$this->contains_nyp = true;

				}

				if ( $product_regular_price > $bundled_product_price )
					$this->on_sale = true;

				// price for simple products gets stored now, for variable products jquery gets the job done
				$this->bundle_price_data[ 'prices' ][ $bundled_product->id ] 			= $bundled_product_price;
				$this->bundle_price_data[ 'regular_prices' ][ $bundled_product->id ] 	= $product_regular_price;

				// no variation data to load - product is simple

				$this->min_bundle_price 		= $this->min_bundle_price + $this->bundled_item_quantities[ $bundled_item_id ] * $bundled_product_price;
				$this->min_bundle_regular_price = $this->min_bundle_regular_price + $this->bundled_item_quantities[ $bundled_item_id ] * $product_regular_price;

				$this->max_bundle_price 		= $this->max_bundle_price + $this->bundled_item_quantities[ $bundled_item_id ] * $bundled_product_price;
				$this->max_bundle_regular_price = $this->max_bundle_regular_price + $this->bundled_item_quantities[ $bundled_item_id ] * $product_regular_price;
			}

			elseif ( $bundled_product->product_type == 'variable' ) {

				// prepare price variable for jquery

				$this->bundle_price_data[ 'prices' ][ $bundled_item_id ] 			= 0;
				$this->bundle_price_data[ 'regular_prices' ][ $bundled_item_id ] 	= 0;

				// get all available attributes and settings

				$this->bundle_attributes[ $bundled_item_id ] = $bundled_product->get_variation_attributes();

				$default_product_attributes = array();

				if ( $this->bundle_defaults_active[ $bundled_item_id ] ) {
					$default_product_attributes = $this->bundle_defaults[ $bundled_item_id ];
				} else {
					$default_product_attributes = ( array ) maybe_unserialize( get_post_meta( $product_id, '_default_attributes', true ) );
				}

				$this->selected_bundle_attributes[ $bundled_item_id ] = apply_filters( 'woocommerce_product_default_attributes', $default_product_attributes );

				// check stock status of parent product

				if ( $this->all_items_in_stock && ( ! $bundled_product->is_in_stock() || ! $bundled_product->has_enough_stock( $this->bundled_item_quantities[ $bundled_item_id ] ) ) ) {
					$this->all_items_in_stock = false;
				}

				// calculate min-max variation prices

				$min_variation_regular_price 	= '';
				$min_variation_price 			= '';
				$max_variation_regular_price 	= '';
				$max_variation_price 			= '';

				// filter variations array to add prices and modify price_html / stock data

				$this->add_bundled_product_get_price_filter( $bundled_item_id );
				add_filter( 'woocommerce_available_variation', array( $this, 'woo_bundles_available_variation' ), 10, 3 );

				if ( $this->enable_bundle_transients ) {
					$transient_name = 'wc_bundled_item_' . $bundled_item_id . '_' . $this->id;

					if ( false === ( $bundled_item_variations = get_transient( $transient_name ) ) ) {
						$bundled_item_variations = $bundled_product->get_available_variations();
						set_transient( $transient_name, $bundled_item_variations );
					}
				} else {
					$bundled_item_variations = $bundled_product->get_available_variations();
				}

				remove_filter( 'woocommerce_available_variation', array( $this, 'woo_bundles_available_variation' ), 10, 3 );
				$this->remove_bundled_product_get_price_filter( $bundled_item_id );

				// check stock status of variations - if all of them are out of stock, the product cannot be purchased

				$variation_in_stock_exists = false;

				// add only active variations

				foreach ( $bundled_item_variations as $variation_data ) {

					if ( ! empty( $variation_data ) )
						$this->available_bundle_variations[ $bundled_item_id ][] = $variation_data;
					else
						continue;

					// check stock status of variation - if one of them is in stock, the product can be purchased

					if ( $variation_data[ 'is_in_stock' ] )
						$variation_in_stock_exists = true;

					// lowest price
					if ( ! is_numeric( $min_variation_regular_price ) || $variation_data[ 'regular_price' ] < $min_variation_regular_price )
						$min_variation_regular_price = $variation_data[ 'regular_price' ];
					if ( ! is_numeric( $min_variation_price ) || $variation_data[ 'price' ] < $min_variation_price )
						$min_variation_price = $variation_data[ 'price' ];

					// highest price
					if ( ! is_numeric( $max_variation_regular_price ) || $variation_data[ 'regular_price' ] > $max_variation_regular_price )
						$max_variation_regular_price = $variation_data[ 'regular_price' ];
					if ( ! is_numeric( $max_variation_price ) || $variation_data[ 'price' ] > $max_variation_price )
						$max_variation_price = $variation_data[ 'price' ];

				}

				if ( $variation_in_stock_exists == false ) {
					$this->all_items_in_stock = false;
				}

				$add = ( $min_variation_regular_price < $min_variation_price ) ? $min_variation_regular_price : $min_variation_price;

				$this->min_bundle_price 		= $this->min_bundle_price + $this->bundled_item_quantities[ $bundled_item_id ] * $add ;
				$this->min_bundle_regular_price = $this->min_bundle_regular_price + $this->bundled_item_quantities[ $bundled_item_id ] * $min_variation_regular_price;

				$add = ( $max_variation_regular_price < $max_variation_price ) ? $max_variation_regular_price : $max_variation_price;

				$this->max_bundle_price 		= $this->max_bundle_price + $this->bundled_item_quantities[ $bundled_item_id ] * $add;
				$this->max_bundle_regular_price = $this->max_bundle_regular_price + $this->bundled_item_quantities[ $bundled_item_id ] * $max_variation_regular_price;
			}

		}

		if ( $this->per_product_pricing_active ) {
			// Saved price is the one of the cheapest combination
			if ( $this->price != $this->min_bundle_price ) {
				update_post_meta( $this->id, '_price', $this->min_bundle_price );
			}

			$this->price = 0;
		}

	}

	function woo_bundles_available_variation( $variation_data, $bundled_product, $bundled_variation ) {

		global $woocommerce_bundles;

		$bundled_item_id = $this->processing_item_id;

		// Update sold individually status

		if ( ! $bundled_variation->is_sold_individually() )
			$this->all_items_sold_individually = false;

		// Disable if certain conditions are met

		if ( $this->variation_filters_active[ $bundled_item_id ] ) {
			if ( ! is_array( $this->allowed_variations[ $bundled_item_id ] ) )
				return array();
			if ( ! in_array( $bundled_variation->variation_id, $this->allowed_variations[ $bundled_item_id ] ) )
				return array();
		}

		if ( $bundled_variation->price === '' ) {
			return array();
		}

		// Modify product id for JS

		$variation_data[ 'product_id' ] = $bundled_item_id;

		// Add price info

		$regular_price 				= $bundled_variation->regular_price;
		$variation_regular_price 	= empty( $regular_price ) ? ( double ) $bundled_variation->price : ( double ) $regular_price;

		// Variations don't filter get_price_html() correctly, so this is necessary
		$bundled_variation->sale_price 	= $bundled_variation->get_price();
		$bundled_variation->price 		= $bundled_variation->get_price();

		if ( $variation_regular_price > $bundled_variation->price )
			$this->on_sale = true;

		$variation_data[ 'regular_price' ] 	= $variation_regular_price;
		$variation_data[ 'price' ]			= $bundled_variation->price;
		$variation_data[ 'price_html' ]		= $this->per_product_pricing_active ? '<span class="price">' . $bundled_variation->get_price_html() . '</span>' : '';

		if ( ! $bundled_variation->is_in_stock() || ! $bundled_variation->has_enough_stock( $this->bundled_item_quantities[ $bundled_item_id ] ) ) {
			$availability 		= array( 'availability' => __( 'Out of stock', 'woocommerce' ), 'class' => 'out-of-stock' );
			$availability_html 	= ( ! empty( $availability['availability'] ) ) ? apply_filters( 'woocommerce_stock_html', '<p class="stock ' . $availability['class'] . '">'. $availability['availability'].'</p>', 	$availability['availability']  ) : '';

			$variation_data[ 'availability_html' ] 	= $availability_html;
			$variation_data[ 'is_in_stock' ] 		= false;
		}

		return $variation_data;
	}

	function add_bundled_product_get_price_filter( $bundled_item_id ) {

		$this->item_id_for_price_filter = $bundled_item_id;

		add_filter( 'woocommerce_get_price', array( $this, 'bundled_product_get_price_filter' ), 100, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'bundled_product_get_price_html_filter' ), 10, 2 );
	}

	function bundled_product_get_price_html_filter( $price_html, $product ) {

		if ( ! empty ( $this->item_id_for_price_filter ) && ! isset( $product->is_filtered_price_html ) ) {

			if ( ! $this->per_product_pricing_active )
				return '';

			$product->sale_price 	= $product->get_price();
			$product->price 		= $product->get_price();

			$product->is_filtered_price_html = 'yes';

			return $product->get_price_html();
		}

		return $price_html;
	}

	function bundled_product_get_price_filter( $price, $product ) {

		if ( ! empty ( $this->item_id_for_price_filter ) ) {

			if ( ! $this->per_product_pricing_active )
				return 0;

			$bundled_item_id = $this->item_id_for_price_filter;

			$regular_price 	= $product->regular_price;
			$price 			= $product->price;

			$discount = $this->bundled_item_discounts[ $bundled_item_id ];

			return empty( $discount ) || empty( $regular_price ) ? $price : ( double ) $regular_price * ( 100 - $discount ) / 100;
		}

		return $price;
	}

	function remove_bundled_product_get_price_filter( $bundled_item_id ) {

		$this->item_id_for_price_filter = '';

		remove_filter( 'woocommerce_get_price', array( $this, 'bundled_product_get_price_filter' ), 100, 2 );
		remove_filter( 'woocommerce_get_price_html', array( $this, 'bundled_product_get_price_html_filter' ), 10, 2 );
	}

	function get_bundle_price_data() {
		return $this->bundle_price_data;
	}

	function get_bundle_attributes() {
		return $this->bundle_attributes;
	}

	function get_bundled_item_quantities() {
		return $this->bundled_item_quantities;
	}

	function get_selected_bundle_attributes() {
		return $this->selected_bundle_attributes;
	}

	function get_available_bundle_variations() {
		return $this->available_bundle_variations;
	}

	function get_bundled_products() {
		return $this->bundled_products;
	}

	function get_price_html( $price = '' ) {

		if ( $this->per_product_pricing_active ) {

			// Get the price
			if ( $this->min_bundle_price > 0 ) :
				if ( $this->is_on_sale() && $this->min_bundle_regular_price !== $this->min_bundle_price ) :

					if ( !$this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_bundle_regular_price, $this->min_bundle_price );

					$price = apply_filters('woocommerce_bundle_sale_price_html', $price, $this);

				else :

					if ( ! $this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= woocommerce_price( $this->min_bundle_price );

					$price = apply_filters('woocommerce_bundle_price_html', $price, $this);

				endif;
			elseif ( $this->min_bundle_price === '' ) :

				$price = apply_filters('woocommerce_bundle_empty_price_html', '', $this);

			elseif ( $this->min_bundle_price == 0 ) :

				if ($this->is_on_sale() && isset($this->min_bundle_regular_price) && $this->min_bundle_regular_price !== $this->min_bundle_price ) :

					if ( ! $this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_bundle_regular_price, __('Free!', 'woocommerce') );

					$price = apply_filters('woocommerce_bundle_free_sale_price_html', $price, $this);

				else :

					if ( !$this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= __('Free!', 'woocommerce');

					$price = apply_filters('woocommerce_bundle_free_price_html', $price, $this);

				endif;

			endif;

		} else {

			if ( $this->price > 0 ) :
				if ($this->is_on_sale() && isset( $this->regular_price ) ) :

					$price .= $this->get_price_html_from_to( $this->regular_price, $this->get_price() );

					$price = apply_filters( 'woocommerce_sale_price_html', $price, $this );

				else :

					$price .= woocommerce_price( $this->get_price() );

					$price = apply_filters( 'woocommerce_price_html', $price, $this );

				endif;
			elseif ( $this->price === '' ) :

				$price = apply_filters('woocommerce_empty_price_html', '', $this);

			elseif ( $this->price == 0 ) :

				if ( $this->is_on_sale() && isset( $this->regular_price ) ) :

					$price .= $this->get_price_html_from_to( $this->regular_price, __('Free!', 'woocommerce') );

					$price = apply_filters( 'woocommerce_free_sale_price_html', $price, $this );

				else :

					$price = __('Free!', 'woocommerce');

					$price = apply_filters( 'woocommerce_free_price_html', $price, $this );

				endif;

			endif;
		}

			return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}

	function is_on_sale() {

		if ( $this->per_product_pricing_active && ! empty( $this->bundled_item_ids ) ) {

			if ( $this->on_sale )
				return true;
			else
				return false;

		} else {

			if ( $this->sale_price && $this->sale_price == $this->price )
				return true;
		}
	}

	/**
	 * Returns whether or not the bundle has any attributes set
	 */
	function has_attributes() {
		// check bundle for attributes
		if ( sizeof( $this->get_attributes() )>0) :
			foreach ( $this->get_attributes() as $attribute ) :
				if ( isset( $attribute['is_visible'] ) && $attribute['is_visible'] ) return true;
			endforeach;
		endif;
		// check all bundled items for attributes
		if ( $this->get_bundled_products() ) {
			foreach ( $this->get_bundled_products() as $bundled_product ) {
				if ( sizeof( $bundled_product->get_attributes() ) >0 ) :
					foreach ( $bundled_product->get_attributes() as $attribute ) :
						if ( isset( $attribute['is_visible'] ) && $attribute['is_visible'] )
							return true;
					endforeach;
				endif;
			}
		}
		return false;
	}


	function is_sold_individually() {
		return parent::is_sold_individually() || $this->all_items_sold_individually;
	}

	function backorders_allowed() {

		if ( ! is_admin() )
			return $this->all_items_in_stock && parent::backorders_allowed();

		return parent::backorders_allowed();

	}

	function is_in_stock() {

		if ( ! is_admin() )
			return $this->all_items_in_stock && parent::is_in_stock();

		return parent::is_in_stock();
	}

	/**
	 * Lists a table of attributes for the bundle page
	 */

	function list_attributes() {

		// show attributes attached to the bundle only
		woocommerce_get_template( 'single-product/product-attributes.php', array(
			'product' => $this
		) );

		foreach ( $this->get_bundled_products() as $bundled_item_id => $bundled_product ) {
			if ( ! $this->per_product_shipping_active )
				$bundled_product->length = $bundled_product->width = $bundled_product->weight = '';
			if ( $bundled_product->has_attributes() ) {
				$GLOBALS['listing_attributes_of'] = $bundled_item_id;
				echo '<h3>'.get_the_title( $bundled_product->id ).'</h3>';
				woocommerce_get_template('single-product/product-attributes.php', array(
					'product' => $bundled_product
				));
			}
		}
		unset( $GLOBALS['listing_attributes_of'] );
	}


}
