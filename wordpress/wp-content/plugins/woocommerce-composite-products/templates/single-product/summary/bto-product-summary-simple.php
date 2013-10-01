<?php
/**
 * Composited Simple Product Summary
 * @version  1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce_bto;

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
		?>

	<div class="bundled_item_wrap">
		<?php
			if ( $per_product_pricing == 'yes' && $product->get_price() !== '' )
				woocommerce_get_template('single-product/summary/bto-product-price.php', array(
					'product' => $product
				), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

			// Add-ons
			do_action( 'woocommerce_composite_product_add_to_cart', $product->id, $component_id );

			// Availability
			$availability = $product->get_availability();

			if ($availability['availability']) {
				echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</p>', $availability['availability'] );
		    }
		?>

		<?php
			if ( $product->is_in_stock() ) {
		?>
			<div class="quantity_button">
		 	<?php
		 		if ( ! $product->is_sold_individually() ) {

		 			$min_q = $quantity_min;
		 			$max_q = $product->get_stock_quantity() === '' ? $quantity_max : min( $quantity_max, $product->get_stock_quantity() );

		 			if ( $min_q == $max_q ) {
		 				?>
		 				<div class="quantity"><input type="number" class="qty input-text text" disabled="disabled" name="quantity" min="<?php echo $min_q; ?>" max="<?php echo $min_q; ?>" value="<?php echo $min_q; ?>" /></div>
		 				<?php
		 			} else
			 			woocommerce_quantity_input( array(
			 				'min_value' => $min_q,
			 				'max_value' => $max_q
			 			) );
		 		} else {
		 			?>
		 			<div class="quantity" style="display:none;"><input class="qty" type="hidden" name="quantity" value="1" /></div>
		 			<?php
		 		}
		 	?>
		 	</div>
		<?php
			}
		?>

	</div>
</div>

