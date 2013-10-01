jQuery( function($){

	// Composite type move stock msg up
	$('.bto_stock_msg').insertBefore('._manage_stock_field');

	// Composite type specific options
	$('body').on('woocommerce-product-type-change', function( event, select_val, select ) {

		if ( select_val == 'bto' ) {

			$('input#_downloadable').prop('checked', false);
			$('input#_virtual').removeAttr('checked');

			$('.show_if_simple').show();
			$('.show_if_external').hide();
			$('.show_if_bto').show();

			$('input#_downloadable').closest('.show_if_simple').hide();
			$('input#_virtual').closest('.show_if_simple').hide();

			$('input#_manage_stock').change();
			$('input#_per_product_pricing_bto').change();
			$('input#_per_product_shipping_bto').change();

		} else {

			$('.show_if_bto').hide();
		}

	});

	$('select#product-type').change();

	// Non-bundled shipping
	$('input#_per_product_shipping_bto').change(function(){

		if ( $('select#product-type').val() == 'bto' ) {
			if ( $('input#_per_product_shipping_bto').is(':checked') ) {
				$('.show_if_virtual').show();
				$('.hide_if_virtual').hide();
				if ( $('.shipping_tab').hasClass('active') )
					$('ul.product_data_tabs li:visible').eq(0).find('a').click();
			} else {
				$('.show_if_virtual').hide();
				$('.hide_if_virtual').show();
			}
		}
	}).change();

	// Show options if pricing is static
	$('input#_per_product_pricing_bto').change(function(){

		if ( $('select#product-type').val() == 'bto' ) {

			if ( $(this).is(':checked') ) {

				$('#_regular_price').attr('disabled', true);
		        $('#_regular_price').val('');
		        $('#_sale_price').attr('disabled', true);
		        $('#_sale_price').val('');

		        $('._tax_class_field').closest('.options_group').hide();
				$('.pricing').hide();

				$('#bto_product_data .config_group .bto_groups .bto_group').each( function() {
					$(this).find('.bto_group_data .group_discount input.group_discount').attr('disabled', false);
					$(this).find('.bto_group_data .group_discount').show();
				} );

			} else {

				$('#_regular_price').removeAttr('disabled');
		        $('#_sale_price').removeAttr('disabled');

		        $('._tax_class_field').closest('.options_group').show();

		        if ( ! $( '#_nyp' ).is( ':checked' ) )
					$('.pricing').show();

				$('#bto_product_data .config_group .bto_groups .bto_group').each( function() {
					$(this).find('.bto_group_data .group_discount input.group_discount').attr('disabled', 'disabled');
					$(this).find('.bto_group_data .group_discount').hide();
				} );
			}
		}

	}).change();

	// nyp support
	$( '#_nyp' ).change( function() {

		if ( $('select#product-type').val() == 'bto' ) {
			if ( $( '#_nyp' ).is( ':checked' ) ) {
				$( 'input#_per_product_pricing_bto' ).prop( 'checked', false ).change();
				$( '.bto_pricing' ).hide();
			} else {
				$( '.bto_pricing' ).show();
			}
		}

	} ).change();

	// Add rows
	$('button.add_bto_group').on('click', function(){

		var size 	= $('.bto_groups .bto_group').size();
		var id 		= 0;

		while( $('.bto_groups').find('div.bto_group input[name="bto_data['+ id +'][title]"]').length > 0 ) {
			id++;
		}

		var hidden 		= '';
		var disabled 	= '';

		// Discount option visibility
		if ( ! $('input#_per_product_pricing_bto').is(':checked') ) {
			hidden 		= 'style="display: none;"';
			disabled 	= 'disabled="disabled"';
		}

		// Add item
		$('.bto_groups').append('<div class="bto_group wc-metabox">\
				<h3>\
					<button type="button" class="remove_row button">' + woocommerce_writepanel_params.remove_label + '</button>\
					<div class="handlediv" title="' + woocommerce_writepanel_params.click_to_toggle + '"></div>\
					<strong class="group_name"></strong>\
				</h3>\
				<div class="bto_group_data">\
					<div class="options_group">\
						<h4>' + bto_bundles_params.component_configuration_title +  '</h4>\
						<div class="group_title">\
							<p class="form-field">\
								<label>' + bto_bundles_params.item_title_label + ':</label>\
								<input type="text" class="group_title" name="bto_data[' + id + '][title]" />\
								<input type="hidden" name="bto_data[' + id + '][position]" class="group_position" value="' + size + '" />\
							</p>\
						</div>\
						<div class="group_description">\
							<p class="form-field">\
								<label>' + bto_bundles_params.item_description_label + ':</label>\
								<textarea class="group_description" name="bto_data[' + id + '][description]" id="group_description_' + id + '" placeholder="" rows="2" cols="20"></textarea>\
							</p>\
						</div>\
						<div class="bto_selector">\
							<p class="form-field">\
								<label>' + bto_bundles_params.select_products_label + ':</label>\
								<select id="bto_ids_' + id + '" name="bto_data[' + id + '][assigned_ids][]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="' + bto_bundles_params.add_products_label + '">\
								</select>\
							</p>\
						</div>\
						<div class="group_default">\
							<p class="form-field">\
								<label>' + bto_bundles_params.item_default_option_label + ':</label>\
								<span><em>' + bto_bundles_params.item_default_option_save_warning + '&hellip;</em></span>\
							</p>\
						</div>\
						<div class="group_quantity_min">\
							<p class="form-field">\
								<label for="group_quantity_min_' + id + '">' + bto_bundles_params.item_quantity_min_label + ':</label>\
								<input type="number" class="group_quantity_min" name="bto_data[' + id + '][quantity_min]"\ id="group_quantity_min_' + id + '" value="1" placeholder="" step="1" min="1">\
							</p>\
						</div>\
						<div class="group_quantity_max">\
							<p class="form-field">\
								<label for="group_quantity_max_' + id + '">' + bto_bundles_params.item_quantity_max_label + ':</label>\
								<input type="number" class="group_quantity_max" name="bto_data[' + id + '][quantity_max]"\ id="group_quantity_max_' + id + '" value="1" placeholder="" step="1" min="1">\
							</p>\
						</div>\
						<div class="group_discount" ' + hidden + '>\
							<p class="form-field">\
								<label for="group_discount_' + id + '">' + bto_bundles_params.item_discount_label + ':</label>\
								<input type="number" class="group_discount" name="bto_data[' + id + '][discount]"\ id="group_discount_' + id + '" value="" placeholder="" step="any" min="0" max="100" ' + disabled + '>\
							</p>\
						</div>\
						<div class="group_optional" >\
							<p class="form-field">\
								<label for="group_optional_' + id + '">' + bto_bundles_params.item_optional_label + ':</label>\
								<input type="checkbox" class="checkbox" name="bto_data[' + id + '][optional]" />\
							</p>\
						</div>\
					</div>\
					<div class="options_group">\
						<h4>' + bto_bundles_params.component_layout_title +  '</h4>\
						<div class="group_hide_product_title" >\
							<p class="form-field">\
								<label for="group_hide_product_title_' + id + '">' + bto_bundles_params.item_hide_product_title_label + ':</label>\
								<input type="checkbox" class="checkbox" name="bto_data[' + id + '][hide_product_title]" />\
							</p>\
						</div>\
						<div class="group_hide_product_description" >\
							<p class="form-field">\
								<label for="group_hide_product_description_' + id + '">' + bto_bundles_params.item_hide_product_description_label + ':</label>\
								<input type="checkbox" class="checkbox" name="bto_data[' + id + '][hide_product_description]" />\
							</p>\
						</div>\
						<div class="group_hide_product_thumbnail" >\
							<p class="form-field">\
								<label for="group_hide_product_thumbnail_' + id + '">' + bto_bundles_params.item_hide_product_thumbnail_label + ':</label>\
								<input type="checkbox" class="checkbox" name="bto_data[' + id + '][hide_product_thumbnail]" />\
							</p>\
						</div>\
					</div>\
				</div>\
			</div>');

		$("#bto_ids_" + id ).chosen();

		$("#bto_ids_" + id ).ajaxChosen({
		    method: 	'GET',
		    url: 		woocommerce_writepanel_params.ajax_url,
		    dataType: 	'json',
		    afterTypeDelay: 100,
		    data:		{
		    	action: 		'woocommerce_json_search_products',
				security: 		woocommerce_writepanel_params.search_products_nonce
		    }
		}, function (data) {

			var terms = {};

		    $.each(data, function (i, val) {
		        terms[i] = val;
		    });

		    return terms;
		});

	});

	$('.bto_groups').on('click', 'button.remove_row', function() {

		var $parent = $(this).parent().parent();

		$parent.remove();
		group_row_indexes();

	});

	function group_row_indexes() {
		$('.bto_groups .bto_group').each(function( index, el ){
			$('.group_position', el).val( parseInt( $(el).index('.bto_groups .bto_group') ) );
		});
	};

	// Initial order
	var bto_groups = $('.bto_groups').find('.bto_group').get();

	bto_groups.sort(function(a, b) {
	   var compA = parseInt($(a).attr('rel'));
	   var compB = parseInt($(b).attr('rel'));
	   return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
	})
	$(bto_groups).each( function(idx, itm) { $('.bto_groups').append(itm); } );


	// Item ordering
	$('.bto_groups').sortable({
		items:'.bto_group',
		cursor:'move',
		axis:'y',
		handle: 'h3',
		scrollSensitivity:40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start:function(event,ui){
			ui.item.css('background-color','#f6f6f6');
		},
		stop:function(event,ui){
			ui.item.removeAttr('style');
			group_row_indexes();
		}
	});


	$('#bto_product_data .expand_all').click(function(){
		$(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > .bto_group_data').show();
		return false;
	});

	$('#bto_product_data .close_all').click(function(){
		$(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > .bto_group_data').hide();
		return false;
	});

});