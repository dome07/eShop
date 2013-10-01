<?php
/**
 * Composited Product Image
 * @version  1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( has_post_thumbnail( $product_id ) ) {
	?>
	<div class="images">
		<a itemprop="image" href="<?php echo wp_get_attachment_url( get_post_thumbnail_id( $product_id ) ); ?>" class="zoom" rel="thumbnails" title="<?php echo get_the_title( get_post_thumbnail_id( $product_id ) ); ?>"><?php echo get_the_post_thumbnail( $product_id, apply_filters( 'bundled_product_large_thumbnail_size', 'shop_thumbnail' ) ) ?></a>
	</div>
	<?php
} ?>
