<?php
/**
 * BTO Cart Button
 * @version 1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce_bto;

$bto_data = $product->get_bto_data();

?>

<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart bto_multipage bto_form bto_form_<?php echo $product->id; echo $added ? ' active' : ''; ?>" method="post" enctype='multipart/form-data' data-overview-title="<?php echo __('Review', 'woocommerce-bto'); ?>" data-container-id="<?php echo $product->id; ?>" <?php echo $product->style == 'paged' ? 'style="display:none;"' : ''; ?>>

	<?php

	if ( $product->style == 'paged' ) {
		?>
		<div class="multipage_title">
			<?php
			$steps = count( $bto_data ) + 1;
			woocommerce_get_template('single-product/bto-item-title.php', array(
				'title' => sprintf( __('Step <span class="step">%d</span> of <span class="steps">%d</span> - Review'), $steps, $steps )
			), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );
			?>
		</div>
		<div class="review">
			<dl>
			<?php
			foreach ( $bto_data as $group_id => $group_data ) {
				echo '<dt><h5>' . $group_data['title'] . ': </h5></dt>';
				echo '<dd class="description_' . $group_id . '"></dd>';
				echo '<dd class="quantity_' . $group_id . '"></dd>';
			}
			?>
			</dl>
		</div>
		<?php
	}

	do_action('woocommerce_before_add_to_cart_button');

	?>
	<div class="bundle_wrap" style="display:none;">
		<div class="bundle_price"></div>
		<?php
			// Bundle Availability
			$availability = $product->get_availability();

			if ($availability['availability'])
				echo apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability['class'].'">'.$availability['availability'].'</p>', $availability['availability'] );
		?>
		<div class="bundle_button">
			<?php
			foreach ( $bto_data as $group_id => $group_data ) {
				?>
				<div class="form_data form_data_<?php echo $group_id; ?>">
					<input type="hidden" class="product_input" name="add-product-to-cart[<?php echo $group_id; ?>]" value="" />
					<input type="hidden" class="quantity_input" name="item_quantity[<?php echo $group_id; ?>]" value="" />
				</div>
				<?php
			}
			if ( !$product->is_sold_individually() )
				woocommerce_quantity_input( array ( 'min_value' => 1 ) );
			?>
			<button type="submit" class="button alt"><?php echo apply_filters('single_add_to_cart_text', __('Add to cart', 'woocommerce'), $product->product_type); ?></button>
		</div>
	</div>

	<?php do_action('woocommerce_after_add_to_cart_button'); ?>

</form>

<?php do_action('woocommerce_after_add_to_cart_form');

if ( $product->style == 'paged' ) {
	?>
	<div class="multipage_progress" data-container-id="<?php echo $product->id; ?>">
		<a class="button prev alt" style="display:none;"></a>
		<a class="button next alt" style="display:none;"></a>
	</div>
	<?php
}
?>
