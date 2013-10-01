<?php
/**
 * Composited Variable Product Summary
 * @version  1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce, $woocommerce_bto;

if ( $hide_product_title != 'yes' )
	woocommerce_get_template('single-product/summary/bto-product-title.php', array(
		'title' => get_the_title( $product->id )
	), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

if ( $hide_product_thumbnail != 'yes' )
	woocommerce_get_template('single-product/summary/bto-product-image.php', array(
		'product_id' => $product->id
	), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

?>

<div class="details">

	<?php

	if ( $hide_product_description != 'yes' )
		woocommerce_get_template('single-product/summary/bto-product-excerpt.php', array(
			'product_id' => $product->id
		), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

	$attributes				= $product->get_variation_attributes();
	$selected_attributes	= $product->get_variation_default_attributes();

	?>

	<div class="variations">
		<?php $loop = 0; foreach ( $attributes as $name => $options ) : $loop++; ?>
			<div class="attribute-options" data-attribute-name="<?php echo $woocommerce->attribute_label( $name ); ?>">
				<label for="<?php echo sanitize_title($name); ?>"><?php echo $woocommerce->attribute_label( $name ); ?></label>
				<select id="<?php echo esc_attr( sanitize_title($name) ); ?>" name="attribute_<?php echo sanitize_title($name); ?>">
					<option value=""><?php echo __( 'Choose an option', 'woocommerce' ) ?>&hellip;</option>
					<?php
						if ( is_array( $options ) ) {

							$selected_value = '';

							if ( isset( $_POST[ 'attribute_' . sanitize_title( $name ) ][ $item_id ] ) && $_POST[ 'attribute_' . sanitize_title( $name ) ][ $item_id ] !== '' )
								$selected_value = $_POST[ 'attribute_' . sanitize_title( $name ) ][ $item_id ];
							else
								$selected_value = ( isset( $selected_attributes[ sanitize_title( $name ) ] ) ) ? $selected_attributes[ sanitize_title( $name ) ] : '';

							// Get terms if this is a taxonomy - ordered
							if ( taxonomy_exists( $name ) ) {

								$orderby = $woocommerce->attribute_orderby( $name );

								switch ( $orderby ) {
									case 'name' :
										$args = array( 'orderby' => 'name', 'hide_empty' => false, 'menu_order' => false );
									break;
									case 'id' :
										$args = array( 'orderby' => 'id', 'order' => 'ASC', 'menu_order' => false );
									break;
									case 'menu_order' :
										$args = array( 'menu_order' => 'ASC' );
									break;
								}

								$terms = get_terms( $name, $args );

								foreach ( $terms as $term ) {
									if ( ! in_array( $term->slug, $options ) )
										continue;

									echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $selected_value, $term->slug, false ) . '>' . apply_filters( 'woocommerce_variation_option_name', $term->name ) . '</option>';
								}
							} else {

								foreach ( $options as $option ) {
									echo '<option value="' . esc_attr( sanitize_title( $option ) ) . '" ' . selected( sanitize_title( $selected_value ), sanitize_title( $option ), false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
								}

							}
						}
					?>
				</select></div><?php
				if ( sizeof( $attributes ) == $loop )
					echo '<a class="reset_variations" href="#reset">' . __( 'Clear selection', 'woocommerce' ) . '</a>';
				?>
	    <?php endforeach;?>
	</div>

	<div class="single_variation_wrap bundled_item_wrap" style="display:none;">
		<?php

		// Add-ons
		do_action( 'woocommerce_composite_product_add_to_cart', $product->id, $component_id );

		?>
		<div class="single_variation"></div>
		<div class="variations_button">
			<input type="hidden" name="variation_id" value="" />
			<?php
				// min-max taken care of by variations code
				// TODO: What if quantity_min = quantity_max?
				 woocommerce_quantity_input();
			?>
		</div>
	</div>

</div>
