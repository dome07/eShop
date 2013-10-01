<?php
/**
 * Composited Product Price
 * @version  1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<p itemprop="price" class="price"><?php echo $product->get_price_html(); ?></p>
