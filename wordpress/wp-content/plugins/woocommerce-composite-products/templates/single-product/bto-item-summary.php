<?php
/**
 * Composite Item Short Description Template
 * @version  1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product, $woocommerce_bto;

?>
<div class="bto_item_summary">
	<div class="product content">
		<?php

		if  ( $prod_id == 'deleted' )
			echo '<p>' . __( 'No options are currently available for this item.', 'woocommerce-bto' ) . '</p>';

		if ( $prod_id > 0 || $prod_id === '0' ) {
			$woocommerce_bto->woo_bto_show_product( $prod_id, $group_id, $container_id );
		}
		?>
	</div>
</div>
