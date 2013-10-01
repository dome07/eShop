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
 * WooCommerce Tab Manager Write Panels
 *
 * Sets up the write panels added by the Tab Manager
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/** Product data Tab panel */
include_once( 'writepanel-product_data-tabs.php' );

/** Product Tab Actions writepanel */
include_once( 'writepanel-product-tab_actions.php' );


/**
 * Save meta boxes
 */
add_action( 'save_post', 'wc_tab_manager_meta_boxes_save', 1, 2 );

/**
 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
 *
 * @access public
 * @param int $post_id post identifier
 * @param object $post post object
 */
function wc_tab_manager_meta_boxes_save( $post_id, $post ) {
	if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( is_int( wp_is_post_revision( $post ) ) ) return;
	if ( is_int( wp_is_post_autosave( $post ) ) ) return;
	if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( 'wc_product_tab' != $post->post_type ) return;

	do_action( 'woocommerce_process_wc_product_tab_meta', $post_id, $post );

	woocommerce_meta_boxes_save_errors();
}


// protect tab posts
add_action( 'publish_wc_product_tab', 'wc_tab_manager_protect_tab', 10, 2 );

/**
 * Automatically protect the product tab posts
 *
 * @access public
 * @param int $post_id the post tab identifier
 * @param object $post the post tab object
 */
function wc_tab_manager_protect_tab( $post_id, $post ) {
	global $wpdb;

	if ( ! $post->post_password ) {

		$wpdb->update( $wpdb->posts, array( 'post_password' => uniqid( 'tab_' ) ), array( 'ID' => $post_id ) );

	}
}


/**
 * Init the meta boxes.
 *
 * Inits the write panels for Product Tabs. Also removes unused default write panels.
 *
 * TODO: how do I prevent other plugins (like SEO) from adding panels, the way that WooCommerce does for the Order/Product/Coupon pages?
 *
 * @access public
 */
function wc_tab_manager_meta_boxes() {

	add_meta_box( 'wc-tab-manager-product-tab-actions', __( 'Tab Actions', WC_Tab_Manager::TEXT_DOMAIN ), 'wc_tab_manager_product_tab_actions_meta_box', 'wc_product_tab', 'side', 'high' );

	remove_meta_box( 'woothemes-settings', 'wc_product_tab' , 'normal' );
}

add_action( 'add_meta_boxes', 'wc_tab_manager_meta_boxes' );
