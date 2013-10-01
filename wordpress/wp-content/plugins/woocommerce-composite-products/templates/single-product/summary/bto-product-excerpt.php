<?php
/**
 * Composited Product Excerpt
 * @version  1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$post = get_post( $product_id );
echo '<p>' . __( $post->post_excerpt ) . '</p>';

?>
