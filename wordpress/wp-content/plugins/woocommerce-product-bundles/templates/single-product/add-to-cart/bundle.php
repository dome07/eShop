<?php
/**
 * Bundled Product Add to Cart
 * @version 3.5.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce, $product, $post, $woocommerce_bundles;

$per_product_pricing = $product->per_product_pricing_active;

?>

<script type="text/javascript">
	if ( ! product_variations )
			var product_variations = new Array();

	var bundle_price_data_<?php echo $post->ID; ?> = <?php echo json_encode( $bundle_price_data ); ?>;
	var bundled_item_quantities_<?php echo $post->ID; ?> = <?php echo json_encode( $bundled_item_quantities ); ?>;

</script>

<?php do_action('woocommerce_before_add_to_cart_form'); ?>

<?php foreach ( $bundled_products as $bundled_item_id => $bundled_product ) {

	if ( $bundled_product->product_type == 'simple' && $bundled_product->get_price() === '' )
		continue;

	// visibility
	$visibility = get_post_meta( $product->id, 'visibility_' . $bundled_item_id, true );
	?>

	<div class="bundled_product bundled_product_summary product" <?php echo ( $visibility == 'hidden' ? 'style=display:none;' : '' ); ?> >

	<?php
		if ( $bundled_product->product_type == 'simple' ) {

			$item_quantity = $product->bundled_item_quantities[ $bundled_item_id ];

			if ( $visibility != 'hidden' ) {

				// title template
				woocommerce_get_template( 'single-product/bundled-product-title.php', array(
						'product' => $bundled_product,
						'quantity' => $item_quantity,
						'custom_title' => get_post_meta( $product->id, 'product_title_' . $bundled_item_id, true )
					), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

				// image template
				if ( get_post_meta( $product->id, 'hide_thumbnail_' . $bundled_item_id, true ) != 'yes' )
					woocommerce_get_template( 'single-product/bundled-product-image.php', array( 'post_id' => $bundled_product->id ), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

			}

			?><div class="details"><?php

				if ( $visibility != 'hidden' ) {

					// description template
					woocommerce_get_template( 'single-product/bundled-product-short-description.php', array(
							'product' => $bundled_product,
							'custom_description' => get_post_meta( $product->id, 'product_description_' . $bundled_item_id, true )
						), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );
				}

				// Availability
				$availability = $bundled_product->get_availability();

				if ( ! $bundled_product->is_in_stock() || ! $bundled_product->has_enough_stock( $item_quantity ) ) {
						$availability = array( 'availability' => __( 'Out of stock', 'woocommerce' ), 'class' => 'out-of-stock' );
				}

				?>

				<form class="cart" data-bundled-item-id="<?php echo $bundled_item_id; ?>" data-product_id="<?php echo $post->ID . str_replace('_', '', $bundled_item_id); ?>" data-bundle-id="<?php echo $post->ID; ?>">

					<?php
					if ( $availability[ 'availability' ] )
						echo apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability[ 'class' ].'">'.$availability[ 'availability' ].'</p>', $availability['availability'] );

					?>
					<div class="bundled_item_wrap">
						<?php

							$product->add_bundled_product_get_price_filter( $bundled_item_id );

							if ( $per_product_pricing ) {

								woocommerce_get_template( 'single-product/bundled-product-price.php', array(
									'bundled_product' => $bundled_product ), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );
							}

							// Compatibility with plugins that normally hook to woocommerce_before_add_to_cart_button
							do_action( 'woocommerce_bundled_product_add_to_cart', $bundled_product->id, $bundled_item_id );

							$product->remove_bundled_product_get_price_filter( $bundled_item_id );

							?>
							<div class="quantity" style="display:none;"><input class="qty" type="hidden" name="quantity" value="<?php echo $item_quantity; ?>" /></div>
					</div>

				</form>

			</div>
			<?php

		} elseif ( $bundled_product->product_type == 'variable' ) {

			?>

			<script type="text/javascript">
				product_variations[ <?php echo $post->ID . str_replace('_', '', $bundled_item_id); ?> ] = <?php echo json_encode( $available_variations[ $bundled_item_id ] ); ?>;
				// pre WC 2.0 compatibility
				product_variations_<?php echo $post->ID . str_replace('_', '', $bundled_item_id); ?> = <?php echo json_encode( $available_variations[ $bundled_item_id ] ); ?>;
			</script>

			<?php

			$item_quantity = $product->bundled_item_quantities[ $bundled_item_id ];

			if ( $visibility != 'hidden' ) {

				// title template
				woocommerce_get_template( 'single-product/bundled-product-title.php', array(
						'product' => $bundled_product,
						'quantity' => $item_quantity,
						'custom_title' => get_post_meta( $product->id, 'product_title_' . $bundled_item_id, true )
					), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

				// image template
				$hide_thumbnail = get_post_meta( $product->id, 'hide_thumbnail_' . $bundled_item_id, true );

				if ( $hide_thumbnail != 'yes' )
					woocommerce_get_template( 'single-product/bundled-product-image.php', array( 'post_id' => $bundled_product->id ), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

			}

			?><div class="details"><?php

				if ( $visibility != 'hidden' ) {

					// description template
					woocommerce_get_template( 'single-product/bundled-product-short-description.php', array(
							'product' => $bundled_product,
							'custom_description' => get_post_meta( $product->id, 'product_description_' . $bundled_item_id, true )
						), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

				}

				?>
				<form class="variations_form cart" data-product_variations="<?php echo esc_attr( json_encode( $available_variations[ $bundled_item_id ] ) ); ?>" data-bundled-item-id="<?php echo $bundled_item_id; ?>" data-product_id="<?php echo $post->ID . str_replace('_', '', $bundled_item_id); ?>" data-bundle-id="<?php echo $post->ID; ?>">
					<div class="variations">
						<?php

						$loop = 0; foreach ( $attributes[ $bundled_item_id ] as $name => $options ) { $loop++; ?>
							<div class="attribute-options">
							<label for="<?php echo sanitize_title($name). '_' . $bundled_item_id; ?>"><?php if ( function_exists('ssc_remove_accents') ) { echo ssc_remove_accents( $woocommerce->attribute_label( $name ) ); } else { echo $woocommerce->attribute_label( $name ); } ?></label>
							<select id="<?php echo esc_attr( sanitize_title( $name ) . '_' . $bundled_item_id ); ?>" name="attribute_<?php echo sanitize_title( $name ); ?>">
								<option value=""><?php echo __('Choose an option', 'woocommerce') ?>&hellip;</option>
								<?php
									if( is_array( $options ) ) {
										if ( empty( $_POST ) )
											$selected_value = ( isset( $selected_attributes[ $bundled_item_id ][ sanitize_title( $name ) ] ) ) ? $selected_attributes[ $bundled_item_id ][ sanitize_title( $name ) ] : '';
										else
											$selected_value = isset( $_POST[ 'attribute_' . sanitize_title( $name ) ][ $bundled_item_id ] ) ? $_POST[ 'attribute_' . sanitize_title( $name ) ][ $bundled_item_id ] : '';


										// Do not show filtered-out (disabled) options
										if ( get_post_meta( $product->id, 'hide_filtered_variations_' . $bundled_item_id, true ) == 'yes' && $product->variation_filters_active[ $bundled_item_id ] && is_array( $product->filtered_variation_attributes[ $bundled_item_id ] ) && array_key_exists( sanitize_title( $name ), $product->filtered_variation_attributes[ $bundled_item_id ] ) ) {

											$options = $product->filtered_variation_attributes[ $bundled_item_id ][ sanitize_title( $name )][ 'slugs' ];
										}

										if ( taxonomy_exists( sanitize_title( $name ) ) ) {
											$args = array( 'menu_order' => 'ASC' );
											$terms = get_terms( sanitize_title($name), $args );

											foreach ( $terms as $term ) {
												if ( !in_array( $term->slug, $options ) ) continue;
												echo '<option value="'. esc_attr( $term->slug ) .'" '.selected( $selected_value, $term->slug, false ).'>'. apply_filters( 'woocommerce_variation_option_name', $term->name ) .'</option>';
											}
										}
										else {
											foreach ( $options as $option ) {
												echo '<option value="'. esc_attr( sanitize_title( $option ) ) .'" '.selected( $selected_value, esc_attr( sanitize_title( $option ) ), false ).'>'. esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) .'</option>';
											}
										}
									}
								?>
							</select></div><?php

							if ( sizeof( $attributes[ $bundled_item_id ] ) == $loop ) {
								echo '<a class="reset_variations" href="#reset_' . $bundled_item_id .'">'.__( 'Clear selection', 'woocommerce' ).'</a>';
							}

						}
					?>

					</div>

					<?php

					$product->add_bundled_product_get_price_filter( $bundled_item_id );

					// Compatibility with plugins that normally hook to woocommerce_before_add_to_cart_button
					do_action( 'woocommerce_bundled_product_add_to_cart', $bundled_product->id, $bundled_item_id );

					$product->remove_bundled_product_get_price_filter( $bundled_item_id );

					?>

					<div class="single_variation_wrap bundled_item_wrap" style="display:none;">
						<div class="single_variation"></div>
						<div class="variations_button">
							<input type="hidden" name="variation_id" value="" />
							<input class="qty" type="hidden" name="quantity" value="<?php echo $item_quantity; ?>" />
						</div>
					</div>

				</form>
			</div>
		<?php
		}
	?>

	</div>

<?php } ?>

<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart bundle_form bundle_form_<?php echo $post->ID; ?>" method="post" enctype='multipart/form-data' data-bundle_price_data="<?php echo esc_attr( json_encode( $bundle_price_data ) ); ?>" data-bundled_item_quantities="<?php echo esc_attr( json_encode( $bundled_item_quantities ) ); ?>" data-bundle-id="<?php echo $post->ID; ?>">

<?php do_action('woocommerce_before_add_to_cart_button'); ?>

	<div class="bundle_wrap" style="display:none;">
		<div class="bundle_price"></div>
		<?php
			// Bundle Availability
			$availability = $product->get_availability();

			if ( $availability[ 'availability' ] )
				echo apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability[ 'class' ].'">'.$availability[ 'availability' ].'</p>', $availability[ 'availability' ] );
		?>
		<div class="bundle_button">
			<?php
			foreach ( $bundled_products as $bundled_item_id => $bundled_product ) {
				if ( $bundled_product->product_type == 'variable' ) {
					?><input type="hidden" name="variation_id[<?php echo $bundled_item_id; ?>]" value="" /><?php
					foreach ( $attributes[ $bundled_item_id ] as $name => $options ) { ?>
						<input type="hidden" name="attribute_<?php echo sanitize_title($name) . '[' . $bundled_item_id . ']'; ?>" value=""><?php
					}
				}
				?>
			<?php
			}
			if ( ! $product->is_sold_individually() ) woocommerce_quantity_input( array ( 'min_value' => 1 ) ); ?>
			<button type="submit" class="button alt"><?php echo apply_filters( 'single_add_to_cart_text', __( 'Add to cart', 'woocommerce' ), $product->product_type ); ?></button>
		</div>
	</div>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

</form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
