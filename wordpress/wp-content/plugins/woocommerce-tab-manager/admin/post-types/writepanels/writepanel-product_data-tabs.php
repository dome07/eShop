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
 * Tab Manager Product Data Panel - Tabs tab
 *
 * Functions for displaying the Tab Manager product data panel Tabs tab
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


add_action( 'woocommerce_product_write_panel_tabs', 'wc_tab_manager_product_tabs_panel_tab' );

/**
 * Adds the "Tabs" tab to the Product Data postbox in the admin product interface
 * @access public
 */
function wc_tab_manager_product_tabs_panel_tab() {
	echo '<li class="product_tabs_tab"><a href="#woocommerce_product_tabs">' . __( 'Tabs', WC_Tab_Manager::TEXT_DOMAIN ) . '</a></li>';
}


add_action( 'woocommerce_product_write_panels', 'wc_tab_manager_product_tabs_panel_content' );

/**
 * Adds the "Tabs" tab panel to the Product Data postbox in the product interface
 * @access public
 */
function wc_tab_manager_product_tabs_panel_content() {
	global $post;

	$tabs = get_post_meta( $post->ID, '_product_tabs', true );

	wc_tab_manager_sortable_product_tabs( $tabs );
}


add_action( 'woocommerce_process_product_meta', 'wc_tab_manager_process_product_meta_tabs_tab', 10, 2 );

/**
 * Create/Update/Delete the product tabs
 *
 * @access public
 * @param int $post_id the post identifier
 * @param object $post the post object
 */
function wc_tab_manager_process_product_meta_tabs_tab( $post_id, $post ) {
	global $wp_filter;

	// explanation of post_save action nesting issue:  http://xplus3.net/2011/08/18/wordpress-action-nesting/
	$wp_filter_index = key( $wp_filter['save_post'] );

	$tabs = wc_tab_manager_process_tabs( $post_id, $post );

	reset( $wp_filter['save_post'] );
	foreach ( array_keys( $wp_filter['save_post'] ) as $key ) {
		if ( $key == $wp_filter_index ) {
			break;
		}
		next( $wp_filter['save_post'] );
	}

	update_post_meta( $post_id, '_product_tabs', $tabs );

	// whether the tab layout defined at the product level should be used
	$override_tab_layout = isset( $_POST['_override_tab_layout'] ) && $_POST['_override_tab_layout'] ? 'yes' : 'no';

	update_post_meta( $post_id, '_override_tab_layout', $override_tab_layout );
}
