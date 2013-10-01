<?php
/**
 * Bundled Product Short Description
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! $product->post_excerpt && $custom_description === '' ) return;
?>
<div class="bundled_product_excerpt product_excerpt">
	<?php echo ( ( $custom_description !== '' ) ? $custom_description : __( $product->post_excerpt ) ); ?>
</div>
