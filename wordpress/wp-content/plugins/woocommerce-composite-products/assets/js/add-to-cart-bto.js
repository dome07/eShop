jQuery(document).ready(function($) {

	if ( ! window['product_variations_backup'] )
		var product_variations_backup 	= [];
	else
		var product_variations_backup 	= window['product_variations_backup'];

	window['product_variations'] = [];

	var ui_style = window['bto_style'];

	// Add-ons support

	// Move totals
	var addons_totals = $( '.bto_form #product-addons-total' );
	$( '.bto_form .bundle_price' ).after( addons_totals );

	// Listeners
	$( '.bto_item .cart' )

		.on( 'change', '.product-addon input, .product-addon textarea, .product-addon select', function() {

			var addon 			= $(this).closest( '.product-addon' );
			var item 			= $(this).closest( '.bto_item' );
			var item_id 		= item.attr( 'data-item-id' );
			var container_id 	= item.attr( 'data-container-id' );


			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .addon-data' ).remove();

			addon.find( '.addon' ).each( function() {

				var addon_clone = $(this).clone();

				if ( $(this).is( 'input' ) ) {

					if ( $(this).attr( 'type' ) == 'checkbox' || $(this).attr( 'type' ) == 'radio' )
						addon_clone.prop( 'checked', $(this).prop( 'checked' ) );
					else if ( $(this).attr( 'type' ) == 'text' )
						addon_clone.val( $(this).val() );
					else if ( $(this).attr( 'type' ) == 'file' ) {
						var addon_new = addon_clone.clone( true );
						addon_clone = $(this);
						$(this).after( addon_new );
						addon_new = $(this).next();
						addon_new.trigger('woocommerce-product-addons-update');
					}

				} else if ( $(this).is( 'select' ) ) {
						addon_clone.val( $(this).val() );
						addon_clone.attr('data-price', $(this).find('option:selected').data('price') );
				}
				else if ( $(this).is( 'textarea' ) ) {
					addon_clone.html( $(this).val() );
				}

				$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id ).append( addon_clone.css( 'display', 'none' ).removeClass( 'addon' ).addClass( 'addon-data' ) );

				attempt_show_bto( container_id );

			} );


		} );


	$( '.bto_item' )

		.on( 'woocommerce-nyp-updated-item', function() {

			var item 			= $(this);
			var container_id 	= item.attr( 'data-container-id' );
			var item_id 		= item.attr( 'data-item-id' );
			var nyp 			= $(this).find( '.cart .nyp' );

			var bundle_price_data = window[ 'bto_price_data' ][ container_id ];

			bundle_price_data[ 'prices' ][ item_id ] = nyp.data( 'price' );

			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .nyp-data' ).remove();

			var nyp_clone = nyp.find( 'input.nyp' ).clone();

			nyp_clone.val( nyp.data( 'price' ) );

			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id ).append( nyp_clone.css( 'display', 'none' ).addClass( 'nyp-data' ) );

			attempt_show_bto( container_id );

		} );


	$( 'form.bto_form' ).on( 'woocommerce-nyp-updated-item', function() {

		var container_id 	= $(this).attr( 'data-container-id' );
		var nyp 			= $(this).find( '.nyp' );

		var bundle_price_data = window[ 'bto_price_data' ][ container_id ];

		bundle_price_data[ 'total' ] = nyp.data( 'price' );

		attempt_show_bto( container_id );
	} );


	$( '.bto_item' )

		.on( 'woocommerce_update_variation_values', function( event, variations ) {

			// animate height of container element
			var summary 	= $(this).find( '.bto_item_summary' );
			var onceHandle 	= window.setTimeout(function() {
				//summary.css( 'height', summary.find( '.content' ).outerHeight() );
			}, 400);

		} )

		.on( 'woocommerce_variation_select_change', function( event ) {

			showhide( $(this) );

			// erase input data in submit form
			var item 			= $(this);
			var container_id 	= item.attr( 'data-container-id' );
			var item_id 		= item.attr( 'data-item-id' );

			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .variation_input' ).remove();
			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .attribute_input' ).remove();

			if ( $(this).find( '.variations .attribute-options select' ).val() === '' )
				attempt_show_bto( container_id );

		} )

		.on( 'woocommerce_variation_select_focusin', function( event ) {

			update_selections( $(this) );

		} )

		.on( 'found_variation', function( event, variation ) {

			// animate height of container element
			var summary 	= $(this).find('.bto_item_summary');
			var onceHandle 	= window.setTimeout(function() {
				//summary.css( 'height', summary.find( '.content' ).outerHeight() );
			}, 400);

			// showhide for paged ui style
			showhide( $(this) );

			// copy input data in submit form
			var item 			= $(this);
			var container_id 	= item.attr('data-container-id');
			var item_id 		= item.attr('data-item-id');

			var variation_data 	= '<input type="hidden" name="variation_id['+ item_id +']" class="variation_input" value="' + variation.variation_id + '"/>';
			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id ).append( variation_data );

			for ( attribute in variation.attributes ) {
				var attribute_data 	= '<input type="hidden" name="' + attribute + '['+ item_id +']" class="attribute_input" value="' + $(this).find('.variations .attribute-options select[name="' + attribute + '"]').val() + '"/>';
				$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id ).append( attribute_data );
			}

			var bundle_price_data = window[ 'bto_price_data' ][ container_id ];

			if ( bundle_price_data['per_product_pricing'] == true ) {

				// put variation price in price table
				bundle_price_data['prices'][ item_id ] = variation.price;
				bundle_price_data['regular_prices'][ item_id ] = variation.regular_price;
			}

			attempt_show_bto( container_id );

		} );


	$( '.bto_item' )

		// On clicking the reset options button
		.on( 'click', '.reset_composite_options', function( event ) {

			var item 		= $(this).closest( '.bto_item' );
			var item_index 	= item.index( '.bto_item' );
			var container_id	= item.attr('data-container-id');

			item.closest( '.summary' ).find( '.bto_item' ).each( function( index ) {

				if ( index >= item_index || ui_style[ container_id ] == 'single' ) {

					var selection 	= $(this).find( '.bto_item_options select' );
					var item 		= $(this);
					selection.attr('disabled', false );
					selection.removeClass('disabled');
					selection.val('').change();
				}


			} );

		} )

		.on( 'focusin', '.bto_item_options select', function( event ) {

			update_selections( $(this).closest('.bto_item') );

		} )

		// Upon changing an option
		.on( 'change', '.bto_item_options select', function( event ) {

			var selection 		= $(this);
			var item 			= $(this).closest('.bto_item');
			var summary 		= item.find('.bto_item_summary');
			var summary_content = item.find('.bto_item_summary .content');
			var item_id 		= item.attr('data-item-id');
			var container_id	= item.attr('data-container-id');

			var load_height 	= summary.height();

			summary.css( '-webkit-transition', 'height 0.2s' );
			summary.css( 'height', load_height );

			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .addon-data' ).remove();

			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .variation_input' ).remove();
			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .attribute_input' ).remove();
			$( '.bto_form_' + container_id + ' .review .description_' + item_id ).html( '' );

			var data = {
				action: 		'woo_bto_show_product',
				product_id: 	$(this).val(),
				item_id: 		item_id,
				container_id: 	container_id
				//security: 		woocommerce_params.add_to_cart_nonce
			};

			if ( data.product_id !== '' ) {

				summary.block({message: null, overlayCSS: {background: '#f6f6f6 url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6 } } );

				// get product info via ajax
				jQuery.post( woocommerce_params.ajax_url, data, function( response ) {

					if ( response != 'error' ) {

						ajax_response = jQuery.parseJSON( response );

						bto_type_data[ container_id ][ item_id ] = ajax_response.product_data.product_type;

						if ( ajax_response.product_data.product_type == 'variable' ) {

							product_variations_backup[ item_id ] = ajax_response.product_data.product_variations;

						} else if ( ajax_response.product_data.product_type == 'simple' || ajax_response.product_data.product_type == 'none' ) {

							window[ 'bto_price_data' ][ container_id ][ 'prices' ][ item_id ] = ajax_response.product_data.price_data.price;
							window[ 'bto_price_data' ][ container_id ][ 'regular_prices' ][ item_id ] = ajax_response.product_data.price_data.regular_price;
						}

						if ( bto_type_data[ container_id ][ item_id ] != 'variable' )
							summary_content.find('*').off();

						// put content in place
						if ( bto_type_data[ container_id ][ item_id ] != 'none' ) {
							summary_content.html( ajax_response.markup );
						} else {
							summary_content.html('');
						}

						init_quantity_buttons( item );

						// disable incompatible products and variations and store input data

						update_selections( item );
						showhide( item );

						// init addons

						if ( window['bto_price_data'][ container_id ]['per_product_pricing'] == true )
							item.find( 'form.cart #product-addons-total' ).data( 'price', window[ 'bto_price_data' ][ container_id ][ 'prices' ][ item_id ] );
						else
							item.find( 'form.cart #product-addons-total' ).data( 'price', 0 );

						// variable items need further selections, while simple/none not
						if ( bto_type_data[ container_id ][ item_id ] == 'variable' ) {
							// fire up variation script
							item.find( 'form.variations_form .variations select' ).change();
						} else {
							attempt_show_bto( container_id );
						}

						item.find( 'form.cart .addon' ).change();

						item.find( 'form.cart .nyp input' ).change();

					} else {

						bto_type_data[ container_id ][ item_id ] == 'none';
						summary_content.find('*').off();
						summary_content.html('');

						$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .product_input' ).val('');

						// disable incompatible products and variations
						update_selections( item );
						showhide( item );

						hide_bundle( container_id );

					}

					var onceHandle 	= window.setTimeout( function() {
						summary.css( 'height', summary_content.outerHeight() );
						summary.unblock();
					}, 450 );
					var onceHandle2 = window.setTimeout(function() {
						summary.attr( 'style', '' );
					}, 1000);


				} );

			} else {

				bto_type_data[ container_id ][ item_id ] == 'none';
				summary_content.find('*').off();
				summary_content.html('');

				$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' .product_input' ).val('');

				// disable incompatible products and variations
				update_selections( item );
				showhide( item );

				hide_bundle( container_id );

				summary.css( 'height', summary_content.outerHeight() );

				var onceHandle2 = window.setTimeout(function() {
					summary.attr( 'style', '' );
				}, 300);
			}

		} );


	function enable_next( item ) {
		var next_item = item.next();
		next_item.find('select').attr('disabled', false );

		var previous_item = item.prev();
		previous_item.find('select').attr('disabled', 'disabled' );
	}


	function enable_previous( item ) {
		var previous_item 			= item.prev();
		var previous_item_prod_id 	= previous_item.find('.bto_item_options select').val();

		if ( previous_item_prod_id > 0 || previous_item_prod_id === '0' ) {
			previous_item.find('select').attr('disabled', false );
		}

		item.next().find('select').attr('disabled', 'disabled' );
	}


	function show_nav_next( item ) {
		var container_id 	= item.attr('data-container-id');
		var next_item 		= item.next();
		var prev_item 		= item.prev();

		$('#product-' + container_id + ' .multipage_progress .next').hide();
		$('#product-' + container_id + ' .multipage_progress .prev').hide();

		if ( next_item.hasClass( 'bto_item' ) ) {
			var next_item_id = next_item.attr('data-item-id');

			$('#product-' + container_id + ' .multipage_progress .next').html( bto_nav_titles[ next_item_id ] + ' >' );
			$('#product-' + container_id + ' .multipage_progress .next').show();

		}

		if ( next_item.hasClass('bto_form') ) {
			var next_item_title = next_item.attr('data-overview-title');

			$('#product-' + container_id + ' .multipage_progress .next').html( next_item_title + ' >' );
			$('#product-' + container_id + ' .multipage_progress .next').show();
		}

		if ( prev_item.hasClass( 'bto_item' ) ) {
			var prev_item_id = prev_item.attr('data-item-id');

			$('#product-' + container_id + ' .multipage_progress .prev').html( '< ' + bto_nav_titles[ prev_item_id ] );
			$('#product-' + container_id + ' .multipage_progress .prev').show();
		}
	}


	function hide_nav_next( item ) {

		var container_id 	= item.attr('data-container-id');
		var prev_item 		= item.prev();

		$('#product-' + container_id + ' .multipage_progress .prev').hide();
		$('#product-' + container_id + ' .multipage_progress .next').hide();

		if ( prev_item.hasClass( 'bto_item' ) ) {
			var prev_item_id 	= prev_item.attr('data-item-id');
			var product_id 		= prev_item.find('.bto_item_options select').val();

			if ( product_id > 0 || product_id === '0' ) {
				$('#product-' + container_id + ' .multipage_progress .prev').html( '< ' + bto_nav_titles[ prev_item_id ] );
				$('#product-' + container_id + ' .multipage_progress .prev').show();
			}
		}
	}


	function multipage_nav( item ) {

		var product_id 		= item.find('.bto_item_options select').val();
		var container_id	= item.attr('data-container-id');
		var item_id 		= item.attr('data-item-id');
		var stock 			= item.find( '.cart .bto_item_summary .bundled_item_wrap .out-of-stock').length;

		if ( !item.hasClass( 'active' ) )
			return false;

		// paged previous / next
		if ( ( product_id > 0 || product_id === '0' ) && stock == 0 ) {
			if ( bto_type_data[ container_id ][ item_id ] == 'variable' ) {
				if ( item.find('.variations_button input[name="variation_id"]').val() != '' ) {
					show_nav_next( item );
				} else {
					hide_nav_next( item );
				}
			} else {
				show_nav_next( item );
			}
		} else {
			hide_nav_next( item );
		}

	}


	function showhide( item ) {

		var product_id 		= item.find('.bto_item_options select').val();
		var container_id	= item.attr('data-container-id');
		var item_id 		= item.attr('data-item-id');
		var reset_options 	= item.find('.bto_item_options .reset_composite_options');

		if ( ( product_id > 0 || product_id === '0' ) ) {

			if ( reset_options.css( 'visibility' ) == 'hidden' )
				reset_options.css( 'visibility','visible' ).hide().fadeIn();

		} else {
			reset_options.css('visibility','hidden');
		}


		// paged nav
		if ( ui_style[container_id] == 'paged' ) {
			multipage_nav( item );
		}
	}


	function update_selections( item ) {

		var product_id 		= item.find('.bto_item_options select').val();
		var item_id 		= item.attr('data-item-id');
		var container_id	= item.attr('data-container-id');
		var current_item_id = item_id;

		// update submit form input data
		$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id ).find('input.product_input').val( $( '#bto_item_options_' + item_id ).val() );

		if ( bto_type_data[ container_id ][ item_id ] == 'variable' ) {
			window['product_variations'][ item_id ] = product_variations_backup[ item_id ];
			if ( ! item.find( '.cart' ).hasClass( 'variations_form' ) )
				item.find( '.cart' ).addClass( 'variations_form' ).wc_variation_form();
		} else {
			item.find( '.cart' ).removeClass( 'variations_form' );
			item.find( '.cart' ).unbind( 'check_variations update_variation_values found_variation' );
			item.find( '.cart' ).find('.reset_variations').unbind( 'click' );
			item.find( '.cart' ).find('.variations select').unbind( 'change focusin' );
		}
	}


	function hide_bundle( bundle_id ) {
		$( '.bto_form_' + bundle_id + ' .bundle_wrap' ).slideUp('200');
	}


	function attempt_show_bto( bundle_id ) {

		var all_set = true;

		var bundled_item_quantities = [];

		var addons_prices = [];

		$('#product-' + bundle_id + ' .bto_item').each( function(){

			var item 		= $(this);
			var item_id 	= item.attr('data-item-id');
			var form_data 	= $( '.bto_form_' + bundle_id + ' .bundle_wrap .bundle_button .form_data_' + item_id );

			var product_input 	= form_data.find( 'input.product_input' ).val();
			var quantity_input 	= form_data.find( 'input.quantity_input' ).val();
			var variation_input = form_data.find( 'input.variation_input' ).val();

			// Save addons prices
			addons_prices[ item_id ] = 0;

			form_data.find( '.addon-data' ).each( function() {

				var addon = $(this);

				if ( addon.is('.addon-custom-price') ) {
					addons_prices[ item_id ] = addons_prices[ item_id ] + parseFloat( addon.val() );
				} else if ( addon.is('.addon-checkbox, .addon-radio') ) {
					if ( addon.is(':checked') )
						addons_prices[ item_id ] = addons_prices[ item_id ] + parseFloat( addon.data('price') );
				} else if ( addon.is('.addon-select') ) {
					if ( addon.val() )
						addons_prices[ item_id ] = addons_prices[ item_id ] + parseFloat( addon.data('price') );
				} else {
					if ( addon.val() )
						addons_prices[ item_id ] = addons_prices[ item_id ] + parseFloat( addon.data('price') );
				}

				if ( ! addons_prices[ item_id ] )
					addons_prices[ item_id ] = 0;
			} );


			if ( bto_type_data[ bundle_id ][ item_id ] == undefined || bto_type_data[ bundle_id ][ item_id ] == '' || product_input === '' )
				all_set = false;
			else if ( bto_type_data[ bundle_id ][ item_id ] != 'none' && quantity_input == '' )
				all_set = false;
			else if ( bto_type_data[ bundle_id ][ item_id ] == 'variable' && variation_input == undefined ) {
				all_set = false;
			} else {
				// Store quantity data
				bundled_item_quantities[ item_id ] = quantity_input;
			}

			// update paged review data
			if ( ui_style[ bundle_id ] == 'paged' && all_set == true ) {
				$( '.bto_form_' + bundle_id + ' .review .description_' + item_id ).html( $( '#bto_item_options_' + item_id + ' option:selected' ).attr('data-title') );

				if ( bto_type_data[ bundle_id ][ item_id ] == 'variable' ) {

					var selected_attribute_description = '';

					var attributes = item.find( '.variations .attribute-options' ).length;

					item.find( '.variations .attribute-options' ).each( function( index ) {
						selected_attribute_description =  selected_attribute_description + $(this).attr( 'data-attribute-name' ) + ': ' + $(this).find('select option:selected').text();
						if ( index !== attributes - 1 )
							selected_attribute_description =  selected_attribute_description + ', ';
					} );

					$( '.bto_form_' + bundle_id + ' .review .description_' + item_id ).html( $( '.bto_form_' + bundle_id + ' .review .description_' + item_id ).html() + '</br>' + selected_attribute_description );
				}

				if ( bto_type_data[ bundle_id ][ item_id ] == 'none' )
					$( '.bto_form_' + bundle_id + ' .review .quantity_' + item_id ).html('');
				else
					$( '.bto_form_' + bundle_id + ' .review .quantity_' + item_id ).html( '<strong>x ' + $( '.bto_form_' + bundle_id + ' .bundle_wrap .bundle_button .form_data_' + item_id + ' input.quantity_input' ).val() + '<strong>' );

			}

		} );


		if ( all_set ) {

			var bundle_price_data = window['bto_price_data'][ bundle_id ];

			if ( ( bundle_price_data['per_product_pricing'] == false ) && ( bundle_price_data['total'] == -1 ) )
				return;

			if ( bundle_price_data['per_product_pricing'] == true ) {
				bundle_price_data['total'] = 0;
				bundle_price_data['regular_total'] = 0;
				for ( item_id in bundle_price_data['prices'] ) {
					bundle_price_data['total'] += ( parseFloat( bundle_price_data['prices'][item_id] ) + parseFloat( addons_prices[ item_id ] ) ) * bundled_item_quantities[item_id];
					bundle_price_data['regular_total'] += ( parseFloat( bundle_price_data['regular_prices'][item_id] ) + parseFloat( addons_prices[ item_id ] ) ) * bundled_item_quantities[item_id];
				}
			} else {
				bundle_price_data['total_backup'] 			= bundle_price_data['total'];
				bundle_price_data['regular_total_backup']	= bundle_price_data['regular_total'];
				for ( item_id in addons_prices ) {
					bundle_price_data['total'] += parseFloat( addons_prices[ item_id ] ) * bundled_item_quantities[item_id];
					bundle_price_data['regular_total'] += parseFloat( addons_prices[ item_id ] ) * bundled_item_quantities[item_id];
				}
			}

			$( '.bto_form_' + bundle_id + ' #product-addons-total' ).data( 'price', bundle_price_data['total'] );
			$( '.bto_form_' + bundle_id ).trigger( 'woocommerce-product-addons-update' );

			if ( bundle_price_data['total'] == 0 )
				$( '.bto_form_' + bundle_id + ' .bundle_price' ).html( '<p class="price"><span class="total">' + bundle_price_data['total_description'] + '</span>'+ bundle_price_data['free'] +'</p>' );
			else {

				var sales_price = number_format ( bundle_price_data['total'], bundle_price_data['woocommerce_price_num_decimals'], bundle_price_data['woocommerce_price_decimal_sep'], bundle_price_data['woocommerce_price_thousand_sep'] );

				var regular_price = number_format ( bundle_price_data['regular_total'], bundle_price_data['woocommerce_price_num_decimals'], bundle_price_data['woocommerce_price_decimal_sep'], bundle_price_data['woocommerce_price_thousand_sep'] );

				var remove = bundle_price_data['woocommerce_price_decimal_sep'];

				if ( bundle_price_data['woocommerce_price_trim_zeros'] == 'yes' && bundle_price_data['woocommerce_price_num_decimals'] > 0 ) {
					for (var i = 0; i < bundle_price_data['woocommerce_price_num_decimals']; i++) { remove = remove + '0'; }
					sales_price = sales_price.replace(remove, '');
					regular_price = regular_price.replace(remove, '');
				}

				var sales_price_format = '';
				var regular_price_format = '';

				if ( bundle_price_data['woocommerce_currency_pos'] == 'left' ) {
					sales_price_format = '<span class="amount">' + bundle_price_data['currency_symbol'] + sales_price + '</span>';
					regular_price_format = '<span class="amount">' + bundle_price_data['currency_symbol'] + regular_price + '</span>'; }
				else if ( bundle_price_data['woocommerce_currency_pos'] == 'right' ) {
					sales_price_format = '<span class="amount">' + sales_price + bundle_price_data['currency_symbol'] +  '</span>';
					regular_price_format = '<span class="amount">' + regular_price + bundle_price_data['currency_symbol'] +  '</span>'; }
				else if ( bundle_price_data['woocommerce_currency_pos'] == 'left_space' ) {
					sales_price_format = '<span class="amount">' + bundle_price_data['currency_symbol'] + '&nbsp;' + sales_price + '</span>';
					regular_price_format = '<span class="amount">' + bundle_price_data['currency_symbol'] + '&nbsp;' + regular_price + '</span>'; }
				else if ( bundle_price_data['woocommerce_currency_pos'] == 'right_space' ) {
					sales_price_format = '<span class="amount">' + sales_price + '&nbsp;' + bundle_price_data['currency_symbol'] +  '</span>';
					regular_price_format = '<span class="amount">' + regular_price + '&nbsp;' + bundle_price_data['currency_symbol'] +  '</span>'; }

				if ( bundle_price_data['regular_total'] > bundle_price_data['total'] ) {
					$('.bto_form_' + bundle_id + ' .bundle_price').html('<p class="price"><span class="total">' + bundle_price_data['total_description'] + '</span><del>' + regular_price_format + '</del>&nbsp<ins>' + sales_price_format + '</ins></p>');
				} else {
					$('.bto_form_' + bundle_id + ' .bundle_price').html('<p class="price"><span class="total">' + bundle_price_data['total_description'] + '</span>'+ sales_price_format +'</p>');
				}
			}

			// reset bundle stock status
			$('.bto_form_' + bundle_id + ' .bundle_wrap p.stock').replaceWith( bundle_stock_status[ bundle_id ] );

			// set bundle stock status as out of stock if any selected variation is out of stock
			$('#product-' + bundle_id + ' .variations_form').each(function(){

				if ( $(this).find('.variations').length > 0 ) {

					var $item_stock_p = $(this).find('p.stock');

					if ( $item_stock_p.hasClass('out-of-stock') ) {
						if ( $('.bto_form_' + bundle_id + ' .bundle_wrap p.stock').length > 0 ) {
							$('.bto_form_' + bundle_id + ' .bundle_wrap p.stock').replaceWith( $item_stock_p.clone() );
						} else {
							$('.bto_form_' + bundle_id + ' .bundle_wrap .bundle_price').after( $item_stock_p.clone() );
						}
					}

				}
			});

			$('.bto_form_' + bundle_id + ' .bundle_wrap').slideDown('200').trigger('show_bundle');

			bundle_price_data['total'] 			= bundle_price_data['total_backup'];
			bundle_price_data['regular_total']	= bundle_price_data['regular_total_backup'];

		} else {
			hide_bundle( bundle_id );
		}
	}


	/**
	 * Initial states and loading
	 */

	$('.bto_item_summary').each( function( index ) {
		var item 			= $(this).closest('.bto_item');
		var container_id	= item.attr('data-container-id');
		var item_id 		= item.attr('data-item-id');

		var product_id 		= item.find('.bto_item_options select').val();
		var reset_options 	= item.find('.bto_item_options .reset_composite_options');

		// initialize height for ajax animations
		var load_height = $(this).height();
		//$(this).css( 'height', load_height );

		// initialize data supplied via POST and single product properties
		update_selections( item );

		// init addons
		if ( window[ 'bto_price_data' ][ container_id ][ 'per_product_pricing' ] == true )
			item.find( 'form.cart #product-addons-total' ).data( 'price', window[ 'bto_price_data' ][ container_id ][ 'prices' ][ item_id ] );
		else
			item.find( 'form.cart #product-addons-total' ).data( 'price', 0 );

		if ( ( product_id > 0 || product_id === '0' ) ) {

			if ( reset_options.css( 'visibility' ) == 'hidden' )
				reset_options.css( 'visibility','visible' ).hide().fadeIn();

		} else {
			reset_options.css('visibility','hidden');
		}

		item.find('form.variations_form .variations select').each( function()  {
			if ( $(this).is(':disabled') ) {
				$(this).removeAttr('disabled');
				$(this).change();
				$(this).attr('disabled', 'disabled');
			} else {
				$(this).change();
			}

		} );

		// initialize quantity form fields
		var qty = item.find('input.qty').val();
		if ( qty > 0 )
			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id ).find('input.quantity_input').val( qty );

	} );


	var bundle_stock_status = [];

	$('.bto_form').each( function() {
		var bundle_id = $(this).attr('data-container-id');

		if ( $(this).find('.bundle_wrap p.stock').length > 0 )
			bundle_stock_status[bundle_id] = $(this).find('.bundle_wrap p.stock').clone().wrap('<p>').parent().html();

		if ( ui_style[ bundle_id ] == 'paged' ) {
			init_multipage_nav( bundle_id );
		}

		// init nyp?
		// $(this).find('input.nyp').change();

		attempt_show_bto( bundle_id );

	} );


	/**
	 * Helper functions
	 */

	function number_format( number, decimals, dec_point, thousands_sep ) {
	    var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
	    var d = dec_point == undefined ? "," : dec_point;
	    var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
	    var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;

	    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}

	function intersect_safe(a, b) {
		var ai=0, bi=0;
		var result = new Array();

		a.sort();
		b.sort();

		while( ai < a.length && bi < b.length )
		{
		 if      (a[ai] < b[bi] ){ ai++; }
		 else if (a[ai] > b[bi] ){ bi++; }
		 else /* they're equal */
		 {
		   result.push(a[ai]);
		   ai++;
		   bi++;
		 }
		}

		return result;
	}


	function init_quantity_buttons(item) {

		var container_id	= item.attr('data-container-id');
		var item_id 		= item.attr('data-item-id');

		var qty = item.find('input.qty').val();

		if ( qty > 0 )
			$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id ).find('input.quantity_input').val( qty );

		// Quantity buttons
		item.find("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" class="plus" />').prepend('<input type="button" value="-" class="minus" />');

		// Target quantity inputs on product pages
		item.find("input.qty:not(.product-quantity input.qty)").each(function(){

			var min = parseFloat( $(this).attr('min') );

			if ( min && min > 0 && parseFloat( $(this).val() ) < min ) {
				$(this).val( min );
			}

		});
	}


	function init_multipage_nav( bundle_id ) {
		var active_item = $('#product-' + bundle_id + ' .summary .active');
		active_item.show();
		multipage_nav( active_item );
	}


	$('.multipage_progress')

	// Upon changing an option
	.on( 'click', '.button', function( event ) {

		var container_id 	= $(this).parent().attr('data-container-id');
		var active_item 	= $('#product-' + container_id + ' .bto_multipage.active');
		var next_item 		= active_item.next();
		var prev_item 		= active_item.prev();

		active_item.hide();

		if ( $(this).hasClass('next') ) {
			if ( next_item.hasClass('bto_multipage') ) {
				active_item.removeClass('active');
				next_item.addClass('active').show();

				var summary = next_item.find('.bto_item_summary');

				if ( summary.hasClass('single') ) {
					//summary.css( 'height', summary.find( '.content' ).outerHeight() );
					summary.removeClass('single');
				}

				$(this).hide();
				multipage_nav( next_item );
			}
		}
		else {
			if ( prev_item.hasClass('bto_item') ) {
				active_item.removeClass('active');
				prev_item.addClass('active').show();

				$(this).hide();
				multipage_nav( prev_item );
			}
		}
	} );


	$('.bto_item')

	// Upon changing an option
	.on( 'change', 'input.qty', function( event ) {

		var field 			= $(this);
		var item 			= field.closest('.bto_item');
		var container_id 	= item.attr('data-container-id');
		var item_id 		= item.attr('data-item-id');

		var qty = field.val();

		// Copy form data
		$( '.bto_form_' + container_id + ' .bundle_wrap .bundle_button .form_data_' + item_id ).find('input.quantity_input').val( qty );

		attempt_show_bto( container_id );
	} );


	/**
	 * Function : dump()
	 * Arguments: The data - array,hash(associative array),object
	 *    The level - OPTIONAL
	 * Returns  : The textual representation of the array.
	 * This function was inspired by the print_r function of PHP.
	 * This will accept some data as the argument and return a
	 * text that will be a more readable version of the
	 * array/hash/object that is given.
	 * Docs: http://www.openjs.com/scripts/others/dump_function_php_print_r.php
	 */
	function dump(arr,level) {
		var dumped_text = "";
		if(!level) level = 0;

		//The padding given at the beginning of the line.
		var level_padding = "";
		for(var j=0;j<level+1;j++) level_padding += "    ";

		if(typeof(arr) == 'object') { //Array/Hashes/Objects
			for(var item in arr) {
				var value = arr[item];

				if(typeof(value) == 'object') { //If it is an array,
					dumped_text += level_padding + "'" + item + "' ...\n";
					dumped_text += dump(value,level+1);
				} else {
					dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
				}
			}
		} else { //Stings/Chars/Numbers etc.
			dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
		}
		return dumped_text;
	}

} );