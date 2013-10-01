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
 * WooCommerce Tab Manager Admin
 *
 * Main admin file which loads all Tab Manager panels and modifications
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once( 'woocommerce-tab-manager-admin-functions.php' );


add_action( 'admin_init', 'wc_tab_manager_admin_init' );

/**
 * Initialize the admin, adding actions to properly display and handle
 * the Tab Manager admin custom tabs and panels
 * @access public
 */
function wc_tab_manager_admin_init() {
	global $pagenow;

	// on the product new/edit page
	if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow ) {

		include_once( 'post-types/writepanels/writepanels-init.php' );

	}
}


add_action( 'admin_head', 'wc_tab_manager_admin_menu_highlight' );

/**
 * Highlight the correct top level admin menu item for the product tab post type add screen
 * @access public
 */
function wc_tab_manager_admin_menu_highlight() {

	global $menu, $submenu, $parent_file, $submenu_file, $self, $post_type, $taxonomy, $wc_tab_manager;

	if ( ( isset( $post_type ) && 'wc_product_tab' == $post_type ) || ( isset( $_GET['page'] ) && WC_TAB_MANAGER::ID == $_GET['page'] ) ) {

		$submenu_file = 'edit.php?post_type=wc_product_tab';
		$parent_file  = 'woocommerce';
	}
}


// include admin functions for the wc_product_tab tab type
include_once( 'post-types/wc_product_tab.php' );

// Default Tab Layout admin screen and persistance code
include_once( 'woocommerce-tab-manager-admin-global-layout.php' );


add_action( 'admin_enqueue_scripts', 'wc_tab_manager_admin_enqueue_scripts', 15 );

/**
 * Add necessary admin scripts
 * @access public
 */
function wc_tab_manager_admin_enqueue_scripts() {

	global $wc_tab_manager;

	// Get admin screen id
	$screen = get_current_screen();

	// on the admin product page
	if ( 'product' == $screen->id || ( isset( $_REQUEST['page'] ) && 'wc-tab-manager' == $_REQUEST['page'] ) ) {
		wp_enqueue_script( 'wc_tab_manager_admin', $wc_tab_manager->plugin_url() . '/assets/js/admin.js', array( 'jquery' ) );

		$wc_tab_manager_admin_params = array(
			'remove_product_tab' => __( 'Remove this product tab?', WC_Tab_Manager::TEXT_DOMAIN ),
			'remove_label'       => __( 'Remove', WC_Tab_Manager::TEXT_DOMAIN ),
			'click_to_toggle'    => __( 'Click to toggle', WC_Tab_Manager::TEXT_DOMAIN ),
			'title_label'        => __( 'Title', WC_Tab_Manager::TEXT_DOMAIN ),
			'title_description'  => __( 'The tab title, this appears in the tab', WC_Tab_Manager::TEXT_DOMAIN ),
			'content_label'      => __( 'Content', WC_Tab_Manager::TEXT_DOMAIN ),
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'get_editor_nonce'   => wp_create_nonce( 'get-editor' ),
		);

		wp_localize_script( 'wc_tab_manager_admin', 'wc_tab_manager_admin_params', $wc_tab_manager_admin_params );
	}

	// we're going to make sortable boxes in our custom default tab layout page, the same as on the product edit page
	if ( isset( $_REQUEST['page'] ) && 'wc-tab-manager' == $_REQUEST['page'] ) {

		wp_enqueue_script( 'woocommerce_writepanel' );
		wp_enqueue_script( 'woocommerce_admin' );
		wp_enqueue_script( 'ajax-chosen' );
		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );

		// keep the woocommerce_writepanel script happy
		$woocommerce_witepanel_params = array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
		);

		wp_localize_script( 'woocommerce_writepanel', 'woocommerce_writepanel_params', $woocommerce_witepanel_params );
	}
}


add_action( 'admin_print_styles-woocommerce_page_wc-tab-manager', 'wc_tab_manager_admin_css' );
add_action( 'admin_print_styles-edit.php', 'wc_tab_manager_admin_css' );

/**
 * Queue required CSS
 * @access public
 */
function wc_tab_manager_admin_css() {
	global $woocommerce, $typenow;

	if ( ! $typenow || 'wc_product_tab' == $typenow ) {
		wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
	}
}


add_filter( 'post_updated_messages', 'wc_tab_manager_product_tab_updated_messages' );

/**
 * Set the product updated messages so they're specific to the Product Tabs
 * @access public
 * @param array $messages array of update messages
 */
function wc_tab_manager_product_tab_updated_messages( $messages ) {
	global $post, $post_ID;

	$messages['wc_product_tab'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __( 'Tab updated.', WC_Tab_Manager::TEXT_DOMAIN ),
		2 => __( 'Custom field updated.', WC_Tab_Manager::TEXT_DOMAIN ),
		3 => __( 'Custom field deleted.', WC_Tab_Manager::TEXT_DOMAIN ),
		4 => __( 'Tab updated.', WC_Tab_Manager::TEXT_DOMAIN),
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Tab restored to revision from %s', WC_Tab_Manager::TEXT_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __( 'Tab updated.', WC_Tab_Manager::TEXT_DOMAIN ),
		7 => __( 'Tab saved.', WC_Tab_Manager::TEXT_DOMAIN ),
		8 => __( 'Tab submitted.', WC_Tab_Manager::TEXT_DOMAIN ),
		9 => sprintf( __( 'Tab scheduled for: <strong>%1$s</strong>.', WC_Tab_Manager::TEXT_DOMAIN ),
		  date_i18n( __( 'M j, Y @ G:i', WC_Tab_Manager::TEXT_DOMAIN ), strtotime( $post->post_date ) ) ),
		10 => __( 'Tab draft updated.', WC_Tab_Manager::TEXT_DOMAIN)
	);

	return $messages;
}


add_action( 'all_admin_notices', 'wc_tab_manager_admin_nav' );

/**
 * Use JavaScript to alter the Product Tab post list/add/edit page header so that they become
 * tabs, with a third tab taking us to the Default Tab Layout custom admin
 * page.
 * @access public
 */
function wc_tab_manager_admin_nav() {
	global $typenow, $wc_tab_manager;
	$screen = get_current_screen();

	if ( 'wc_product_tab' == $typenow ) {
		$tabs_active = '';
		$edit_tab_label = __( 'Add Global Tab', WC_Tab_Manager::TEXT_DOMAIN );
		$edit_active = '';
		$search_results = '';

		if ( 'add' == $screen->action ) {
			$edit_active    = 'nav-tab-active';
		} elseif ( isset( $_REQUEST['action'] ) && 'edit' == $_REQUEST['action'] ) {
			$edit_active    = 'nav-tab-active';
			$edit_tab_label = __( 'Edit Tab', WC_Tab_Manager::TEXT_DOMAIN );
		} else $tabs_active = 'nav-tab-active';

		if ( ! empty( $_REQUEST['s'] ) )
			$search_results = sprintf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', get_search_query() );
		?>
		<style type="text/css">
			.icon32-posts-wc_product_tab { background-position: -243px -5px !important; }
		</style>
		<script type="text/javascript">
			jQuery( function($){
				$('h2').addClass( 'nav-tab-wrapper woo-nav-tab-wrapper' );
				$('h2').html(
					'<a class="nav-tab <?php echo $tabs_active; ?>" href="<?php echo admin_url( 'edit.php?post_type=wc_product_tab' ); ?>"><?php echo esc_js( __( 'Tabs', WC_Tab_Manager::TEXT_DOMAIN ) ); ?></a>' +
					'<a class="nav-tab <?php echo $edit_active; ?>" href="<?php echo admin_url( 'post-new.php?post_type=wc_product_tab' ); ?>"><?php echo esc_js( $edit_tab_label ); ?></a>' +
					'<a class="nav-tab" href="<?php echo admin_url( 'admin.php?page=' . WC_Tab_Manager::ID ); ?>"><?php echo esc_js( __( 'Default Tab Layout', WC_Tab_Manager::TEXT_DOMAIN ) ); ?></a>' +
					'<?php echo $search_results; ?>');
			});
		</script>
		<?php
	}
}


add_action( 'admin_menu', 'wp_tab_manager_register_layout_page' );

/**
 * Registers the Default Tab Layout page, which I combine with the product tabs
 * list/add/edit page to act as a single Tab Manager submenu
 * @access public
 */
function wp_tab_manager_register_layout_page() {

	global $wc_tab_manager;

	add_submenu_page( 'woocommerce',                                     // parent menu
	                  __( 'WooCommerce Tab Manager', WC_Tab_Manager::TEXT_DOMAIN ), // page title
	                  null,                                              // menu title  (null so it doesn't appear)
	                  'manage_woocommerce_tab_manager',                  // capability
	                  WC_Tab_Manager::ID,                               // unique menu slug
	                  'wc_tab_manager_render_layout_page' );             // callback
}
