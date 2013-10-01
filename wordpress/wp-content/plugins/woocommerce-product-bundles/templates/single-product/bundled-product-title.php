<?php
/**
 * Bundled Product Title
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<h2 class="bundled_product_title product_title"><?php
	$title = get_the_title( $product->id );
	echo ( ( $custom_title !== '' ) ? $custom_title : $title ) . ( ( $quantity > 1 ) ? ' &times; '. $quantity : '' );
?></h2>
