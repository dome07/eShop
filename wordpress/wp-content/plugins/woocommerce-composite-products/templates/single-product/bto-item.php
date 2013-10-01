<?php
/**
 * Composite Item Single Page Template
 * @version  1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product, $woocommerce_bto;

?>
<div class="bto_item product <?php echo $step == 1 ? 'active first' : ''; echo $step == $steps ? 'last' : ''; ?>" data-item-id="<?php echo $group_id; ?>" data-container-id="<?php echo $product->id; ?>">
	<script type="text/javascript">

		if ( ! compatibility_data )
				var compatibility_data = new Array();

	</script>
<?php

	woocommerce_get_template('single-product/bto-item-title.php', array(
		'title' => $group_data[ 'title' ]
	), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

	if ( $group_data[ 'description' ] != '' )
		echo '<p class="description">' . $group_data[ 'description' ] . '</p>';

	$prod_id = '';

	if ( isset( $_POST[ 'add-product-to-cart' ][ $group_id ] ) && $_POST[ 'add-product-to-cart' ][ $group_id ] !== '' )
		$prod_id = $_POST[ 'add-product-to-cart' ][ $group_id ];
	else
		$prod_id = isset( $group_data[ 'default_id' ] ) ? $group_data[ 'default_id' ] : '';

	if ( get_post_status( $prod_id ) != 'publish' )
		$prod_id = 'deleted';

	if ( $group_data[ 'optional' ] != 'yes' && count( $group_data[ 'assigned_ids' ] ) == 1 ) {
		$prod_id = $group_data[ 'assigned_ids' ][0];

		if ( $prod_id != 'deleted' ) {
			?>
			<div class="bto_item_options" style="display:none">
				<select id="bto_item_options_<?php echo $group_id; ?>" name="bto_selection_<?php echo $group_id; ?>">
					<option data-title="<?php echo get_the_title( $prod_id ); ?>" value="<?php echo $prod_id; ?>"></option>
				</select>
			</div>
			<?php
		}
	} else

		woocommerce_get_template('single-product/bto-item-options.php', array(
			'group_id'				=> $group_id,
			'title'					=> $group_data[ 'title' ],
			'group_options' 		=> $group_data[ 'assigned_ids' ],
			'optional' 				=> $group_data[ 'optional' ],
			'discount'				=> isset( $group_data[ 'discount' ] ) ? $group_data[ 'discount' ] : 0,
			'default_option'		=> isset( $group_data[ 'default_id' ] ) ? $group_data[ 'default_id' ] : '',
			'per_product_pricing' 	=> $product->per_product_pricing
		), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

?>
	<form class="cart" data-product_id="<?php echo $group_id; ?>">
<?php

	woocommerce_get_template('single-product/bto-item-summary.php', array(
		'description' 	=> $group_data[ 'description' ],
		'prod_id'		=> $prod_id,
		'group_id'		=> $group_id,
		'container_id'	=> $product->id
	), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

?>
	</form>
</div>
<?php

?>
