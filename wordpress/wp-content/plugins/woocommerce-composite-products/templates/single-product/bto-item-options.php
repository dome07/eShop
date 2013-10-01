<?php
/**
 * Composite Item Options Drop-Down Template
 * @version  1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce_bto;

?>
<div class="bto_item_options">
	<select id="bto_item_options_<?php echo $group_id; ?>" name="bto_selection_<?php echo $group_id; ?>">
		<option value=""><?php echo __( 'Select an option', 'woocommerce-bto' ); ?>&hellip;</option>
		<?php

		$selected_value = '';

		if ( isset( $_POST['add-product-to-cart'][ $group_id ] ) && $_POST['add-product-to-cart'][ $group_id ] !== '' )
			$selected_value = $_POST['add-product-to-cart'][ $group_id ];
		else
			$selected_value = $default_option;

		if ( $optional == 'yes' ) {
			?>
			<option data-title="<?php echo __( 'None', 'woocommerce-bto' ); ?>" value="0" <?php echo selected( $selected_value, '0', false ); ?>><?php echo __( 'None', 'woocommerce-bto' ); ?></option>
			<?php
		}

		foreach ( $group_options as $product_id ) {

			if ( get_post_status( $product_id ) != 'publish' )
				continue;

			?>
			<option data-title="<?php echo get_the_title( $product_id ); ?>" value="<?php echo $product_id; ?>" <?php echo selected( $selected_value, $product_id, false ); ?>><?php

				echo get_the_title( $product_id );

				if ( $per_product_pricing == 'yes' ) {

					$woocommerce_bto->woo_bto_add_show_product_filters( array( 'discount' => $discount, 'per_product_pricing' => $per_product_pricing ) );

					echo apply_filters( 'woocommerce_composited_product_price', '', $group_id, $product_id ); // To show prices, replace empty string with: ' - ' . strip_tags( preg_replace( '#<del.*?</del>#', __( 'Sale:', 'woocommerce-bto' ) . ' ', get_product( $product_id )->get_price_html() ) )

					$woocommerce_bto->woo_bto_remove_show_product_filters();

				}
			?>
			</option>
			<?php
		}
	?>
	</select>
	<a class="reset_composite_options" href="#reset_composite"><?php echo __( 'Clear options', 'woocommerce-bto' ); ?></a>
</div>
