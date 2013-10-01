<?php
/**
 * Bundled Product Image
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

?>
<div class="images">

	<?php if ( has_post_thumbnail( $post_id ) ) { ?>

		<a itemprop="image" href="<?php echo wp_get_attachment_url( get_post_thumbnail_id( $post_id ) ); ?>" class="zoom" rel="thumbnails" title="<?php echo get_the_title( get_post_thumbnail_id( $post_id ) ); ?>"><?php echo get_the_post_thumbnail( $post_id, apply_filters( 'bundled_product_large_thumbnail_size', 'shop_thumbnail' ), array(
			'title'	=> get_the_title( get_post_thumbnail_id( $post_id ) ),
		) ); ?></a>

	<?php } ?>

</div>
