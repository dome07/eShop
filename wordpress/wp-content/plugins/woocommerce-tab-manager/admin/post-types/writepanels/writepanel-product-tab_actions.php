<?php
/**
 * WooCommerce Tab Manager
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Tab Manager to newer
 * versions in the future. If you wish to customize WooCommerce Tab Manager for your
 * needs please refer to http://docs.woothemes.com/document/tab-manager/
 *
 * @package     WC-Tab-Manager/Admin
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2013, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * Tab Manager Tab Actions panel
 *
 * Functions for displaying the Tab Manager Tab Actions panel on the Edit Tab page
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Display the product tab actions meta box.
 *
 * Displays the product actions meta box - buttons for creating and deleting the tab
 *
 * @access public
 * @param object $post product post object
 */
function wc_tab_manager_product_tab_actions_meta_box( $post ) {
	?>
	<style type="text/css">
		#edit-slug-box, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
		.wc_product_tab_actions li {
			border-bottom: 1px solid #DDDDDD;
			border-top: 1px solid #FFFFFF;
			line-height: 1.6em;
			margin: 0;
			padding: 6px 0;
		}
		.wc_product_tab_actions li:last-child {
		    border-bottom: 0 none;
		}
	</style>
	<ul class="wc_product_tab_actions">
		<li class="wide"><input type="submit" class="button button-primary tips" name="publish" value="<?php _e( 'Save Tab', WC_Tab_Manager::TEXT_DOMAIN ); ?>" data-tip="<?php _e( 'Save/update the tab', WC_Tab_Manager::TEXT_DOMAIN ); ?>" /></li>

		<?php do_action( 'woocommerce_tab_manager_product_tab_actions', $post->ID ); ?>

		<li class="wide">
		<?php
		if ( current_user_can( "delete_post", $post->ID ) ) {
			if ( ! EMPTY_TRASH_DAYS )
				$delete_text = __( 'Delete Permanently', WC_Tab_Manager::TEXT_DOMAIN );
			else
				$delete_text = __( 'Move to Trash', WC_Tab_Manager::TEXT_DOMAIN );
			?>
		<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_attr( $delete_text ); ?></a><?php
		} ?>
		</li>
	</ul>
	<?php
}
