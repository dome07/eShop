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
 * Admin functions for the wc_product_tab post type
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


add_action( 'admin_print_scripts', 'wc_tab_manager_disable_autosave_for_product_tabs' );

/**
 * Disable the auto-save functionality for Tabs.
 *
 * @access public
 */
function wc_tab_manager_disable_autosave_for_product_tabs(){
	global $post;

	if ( $post && 'wc_product_tab' === get_post_type( $post->ID ) ) {
		wp_dequeue_script( 'autosave' );
	}
}


add_filter( 'bulk_actions-edit-wc_product_tab', 'wc_tab_manager_edit_product_tab_bulk_actions' );

/**
 * Remove the bulk edit action for product tabs, it really isn't useful
 *
 * @access public
 * @param array $actions associative array of action identifier to name
 *
 * @return array associative array of action identifier to name
 */
function wc_tab_manager_edit_product_tab_bulk_actions( $actions ) {

	unset( $actions['edit'] );

	return $actions;
}


add_filter( 'views_edit-wc_product_tab', 'wc_tab_manager_edit_product_tab_views' );

/**
 * Modify the 'views' links, ie All (3) | Publish (1) | Draft (1) | Private (2) | Trash (3)
 * shown above the product tabs list table, to hide the publish/private states,
 * which are not important and confusing for product tab objects.
 *
 * @access public
 * @param array $views associative-array of view state name to link
 *
 * @return array associative array of view state name to link
 */
function wc_tab_manager_edit_product_tab_views( $views ) {

	// publish and private are not important distinctions for product tabs
	unset( $views['publish'], $views['private'], $views['future'] );

	return $views;
}


add_filter( 'manage_edit-wc_product_tab_columns', 'wc_tab_manager_edit_product_tab_columns' );

/**
 * Columns for product tab page
 *
 * @access public
 * @param array $columns associative-array of column identifier to header names
 *
 * @return array associative-array of column identifier to header names for the product tabs page
 */
function wc_tab_manager_edit_product_tab_columns( $columns ){

	$columns = array();

	$columns["cb"]   = '<input type="checkbox" />';
	$columns["name"] = __( "Name", WC_Tab_Manager::TEXT_DOMAIN );
	$columns["type"] = __( "Tab Type", WC_Tab_Manager::TEXT_DOMAIN );
	$columns["parent-product"] = __( "Parent Product", WC_Tab_Manager::TEXT_DOMAIN );

	return $columns;
}


add_action( 'manage_wc_product_tab_posts_custom_column', 'wc_tab_manager_custom_product_tab_columns', 2 );


/**
 * Custom Column values for product tabs page
 *
 * @access public
 * @param string $column column identifier
 */
function wc_tab_manager_custom_product_tab_columns( $column ) {
	global $post, $woocommerce, $wc_tab_manager;

	switch ( $column ) {

		case "name":

			$edit_link = get_edit_post_link( $post->ID );
			$title = '<a class="row-title" href="' . $edit_link . '">' . _draft_or_post_title() . '</a>';

			// add the parent product name if any
			if ( $post->post_parent ) {
				$parent = $wc_tab_manager->get_product( $post->post_parent );
				$title = $parent->get_title() . ' - ' . $title;
			}
			echo '<strong>' . $title;

			// display post states a little more selectively than _post_states( $post );
			if ( 'draft' == $post->post_status ) {
				echo " <span class='post-state'>(" . __( 'Draft', WC_Tab_Manager::TEXT_DOMAIN ) . ')</span>';
			}

			echo '</strong>';

			// Get actions
			$actions = array();

			$actions['id'] = 'ID: ' . $post->ID;

			$post_type_object = get_post_type_object( $post->post_type );

			if ( current_user_can( $post_type_object->cap->delete_post, $post->ID ) ) {
				if ( 'trash' == $post->post_status )
					$actions['untrash'] = "<a title='" . __( 'Restore this item from the Trash', WC_Tab_Manager::TEXT_DOMAIN ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-' . $post->post_type . '_' . $post->ID ) . "'>" . __( 'Restore', WC_Tab_Manager::TEXT_DOMAIN ) . "</a>";
				elseif ( EMPTY_TRASH_DAYS )
					$actions['trash'] = "<a class='submitdelete' title='" . __( 'Move this item to the Trash', WC_Tab_Manager::TEXT_DOMAIN ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash', WC_Tab_Manager::TEXT_DOMAIN ) . "</a>";
				if ( 'trash' == $post->post_status || ! EMPTY_TRASH_DAYS )
					$actions['delete'] = "<a class='submitdelete' title='" . __( 'Delete this item permanently', WC_Tab_Manager::TEXT_DOMAIN ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', WC_Tab_Manager::TEXT_DOMAIN ) . "</a>";
			}

			$actions = apply_filters( 'post_row_actions', $actions, $post );

			echo '<div class="row-actions">';

			$i = 0;
			$action_count = count( $actions );

			foreach ( $actions as $action => $link ) {
				( $i == $action_count - 1 ) ? $sep = '' : $sep = ' | ';
				echo '<span class="' . sanitize_html_class( $action ) . '">' . $link . $sep . '</span>';
				$i++;
			}
			echo '</div>';
		break;

		case "type":

			if ( $post->post_parent ) {
				_e( 'Product', WC_Tab_Manager::TEXT_DOMAIN );
			} else {
				_e( 'Global', WC_Tab_Manager::TEXT_DOMAIN );
			}

		break;

		case "parent-product":

			if ( $post->post_parent ) {
				$parent = $wc_tab_manager->get_product( $post->post_parent );
				echo '<a href="' . get_edit_post_link( $parent->id ) . '">' . $parent->get_title() . '</a>';
			} else {
				echo '<em>' . __( 'N/A', WC_Tab_Manager::TEXT_DOMAIN ) . '</em>';
			}

		break;
	}
}


add_filter( 'parse_query', 'wc_tab_manager_admin_product_tab_filter_query' );

/**
 * On the Tabs page filter by tab type
 *
 * @access public
 * @param WP_Query $query the query object
 *
 * @return array
 */
function wc_tab_manager_admin_product_tab_filter_query( $query ) {
	global $typenow, $wpdb;

	if ( 'wc_product_tab' == $typenow && isset( $_GET['product_tab_type'] ) ) {

		if ( 'global' == $_GET['product_tab_type'] ) $query->query_vars['post_parent'] = 0;
		elseif ( 'product' == $_GET['product_tab_type'] ) $query->query_vars['post_parent'] = " AND {$wpdb->posts}.post_parent != 0 ";

	}

	return $query;
}


add_action( 'restrict_manage_posts', 'wc_tab_manager_product_tabs_by_type', 20 );

/**
 * Render the "Show All Types" dropdown filter menu on the Product Tabs
 * page so that tabs can be filtered on their type (product/global)
 * @access public
 */
function wc_tab_manager_product_tabs_by_type() {
	global $typenow, $wp_query, $wpdb;

	if ( 'wc_product_tab' == $typenow ) {

		$product_tab_type = isset( $_GET['product_tab_type'] ) ? $_GET['product_tab_type'] : '';

		$output = "<select name='product_tab_type' id='dropdown_product_tab_type'>";
		$output .= '<option value="">' . __( 'Show all Tabs', WC_Tab_Manager::TEXT_DOMAIN ) . '</option>';
		$output .= '<option value="product"' . selected( 'product', $product_tab_type, false ). '>' . __( 'Show product tabs', WC_Tab_Manager::TEXT_DOMAIN ) . '</option>';
		$output .= '<option value="global"' . selected( 'global', $product_tab_type, false ). '>' . __( 'Show global tabs', WC_Tab_Manager::TEXT_DOMAIN ) . '</option>';
		$output .="</select>";

		echo $output;
	}
}


add_filter( 'posts_join', 'wc_tab_manager_tabs_posts_join', 10, 2 );

/**
 * Modify the query to join back onto the posts table for the parent products to exclude
 * tabs for those that are in the trash.  The only drawback is that any global tabs
 * are returned multiple times, once for each product.  I deal with this by using
 * the groupby filter below.  Performance concerns?
 *
 * @since 1.0.6
 * @param string $join the join query
 * @param WP_Query $query the query object
 */
function wc_tab_manager_tabs_posts_join( $join, $query ) {
	global $wpdb, $typenow;

	if ( 'wc_product_tab' == $typenow ) {
		$join .= " JOIN {$wpdb->posts} AS product_parents ON ( {$wpdb->posts}.post_parent = 0 OR ( {$wpdb->posts}.post_parent = product_parents.ID AND product_parents.post_status != 'trash' ) )";
	}

	return $join;
}


add_filter( 'posts_groupby', 'wc_tab_manager_tabs_posts_groupby', 10, 2 );

/**
 * Group the returned tab posts for the Tabs table by ID.  This is to compensate
 * for the extra global tabs returend by the join query above.
 *
 * @since 1.0.6
 * @param string $groupby the group by query
 * @param WP_Query $query the query object
 */
function wc_tab_manager_tabs_posts_groupby( $groupby, $query ) {
	global $wpdb, $typenow;

	if ( 'wc_product_tab' == $typenow )
		$groupby = "{$wpdb->posts}.ID";

	return $groupby;
}


add_action( 'delete_post', 'wc_tab_manager_delete_post' );

/**
 * Invoked when a WordPress post is deleted.  If the post is a product
 * with child tabs, delete them as well to avoid leaving any orphans.
 *
 * @access public
 * @param int $post_id post identifier
 */
function wc_tab_manager_delete_post( $post_id ) {
	global $wc_tab_manager;

	if ( ! current_user_can( 'delete_posts' ) || ! $post_id ) return;

	// does this post have any attached product tabs?
	$posts = get_posts( array( 'numberposts' => -1, 'post_type' => 'wc_product_tab', 'post_parent' => $post_id ) );

	if ( $posts ) {
		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID );
		}
	}
}


add_action( 'woocommerce_duplicate_product', 'wc_tab_manager_duplicate_product', 10, 2 );

/**
 * Invoked when a product is duplicated and duplicates any product tabs
 *
 * @since 1.0.6
 */
function wc_tab_manager_duplicate_product( $new_id, $original_post ) {
	$tabs = get_post_meta( $new_id, '_product_tabs', true );

	if ( is_array( $tabs ) ) {

		$is_updated = false;
		foreach ( $tabs as $key => $tab ) {
			if ( 'product' === $tab['type'] ) {
				$tab_post = get_post( $tab['id'] );

				$new_tab_data = array(
					'post_title'    => $tab_post->post_title,
					'post_content'  => $tab_post->post_content,
					'post_status'   => 'publish',
					'ping_status'   => 'closed',
					'post_author'   => get_current_user_id(),
					'post_type'     => 'wc_product_tab',
					'post_parent'   => $new_id,
					'post_password' => uniqid( 'tab_' ), // Protects the post just in case
				);

				// link up the product to its new tab
				$tabs[ $key ]['id'] = wp_insert_post( $new_tab_data );
				$is_updated = true;
			}
		}

		if ( $is_updated )
			update_post_meta( $new_id, '_product_tabs', $tabs );
	}
}
