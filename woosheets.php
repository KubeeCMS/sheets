<?php # -*- coding: utf-8 -*-
/**
 * Manage and Sincronize Orders with Google Spreadsheet.
 *
 * @package      Sheets
 * @category     Plugin
 * @author       KubeeCMS
 * @copyright    Copyright (c) 2012-2020, KubeeCMS - KUBEE
 * @license      GPL-2.0-or-later
 * @link         https://github.com/KubeeCMS/sheets/
 * @link         https://github.com/KubeeCMS/
 *
 * @wordpress-plugin
 * Plugin Name:  Sheets
 * Plugin URI:   https://github.com/KubeeCMS/sheets/
 * Description:  Manage and Sincronize Orders with Google Spreadsheet.
 * Version:      4.4.0
 * Author:       KubeeCMS - KUBEE
 * Author URI:   https://github.com/KubeeCMS/
 * License:      GPL-2.0-or-later
 * License URI:  https://opensource.org/licenses/GPL-2.0
 * Text Domain:  woosheets
 * Domain Path:  /i18n/languages/
 * Network:      true
 * Requires WP:  5.5
 * Requires PHP: 7.3
 * WC tested up to: 5.5.0
 *
 * Copyright (c) 2012-2020 KubeeCMS - KUBEE
 *
 *     This file is part of Multisite Toolbar Additions,
 *     a plugin for WordPress.
 *
 *     Sheets is free software:
 *     You can redistribute it and/or modify it under the terms of the
 *     GNU General Public License as published by the Free Software
 *     Foundation, either version 2 of the License, or (at your option)
 *     any later version.
 *
 *     KCMS Additions are distributed in the hope that
 *     it will be useful, but WITHOUT ANY WARRANTY; without even the
 *     implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 *     PURPOSE. See the GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with WordPress. If not, see <http://www.gnu.org/licenses/>.
 */



 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
define( 'WOOSHEETS_PLUGIN_SECURITY', 1 );
define( 'WOOSHEETS_VERSION', "4.4" );
define( 'WOOSHEETS_PLUGIN_ID', '22636997' );
define( 'WOOSHEETS_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WOOSHEETS_DIRECTORY', dirname( plugin_basename( __FILE__ ) ) );
define( 'WOOSHEETS_PLUGIN_SLUG', WOOSHEETS_DIRECTORY . '/' . basename( __FILE__ ) );
if ( ! class_exists( 'Wc_WooSheets_Dependencies' ) )
	require_once trailingslashit(dirname(__FILE__)).'includes/class-wc-woosheets-dependencies.php';
// Include the main WooCommerce class.
if ( ! class_exists( 'Wc_WooSheets_Setting' ) ) {
	include_once  WOOSHEETS_PLUGIN_PATH . '/includes/class-wc-woosheets-setting.php';
	require_once( WOOSHEETS_PLUGIN_PATH . '/includes/class-woosheets-update-updater.php' );
	require_once( WOOSHEETS_PLUGIN_PATH . '/includes/class-woosheets-update-licenser.php');
}
WooSheets_License()->init();
WooSheets_Updater()->init();
/** LICENSE functions **/
function WooSheets_License() {
	return WooSheets_Update_Licenser::instance();
}
/** UPDATE functions **/
function WooSheets_Updater() {
	return WooSheets_Update_Updater::instance();
}
//Check WCGS Dependency Class and WooCommerce Activation
if(Wc_WooSheets_Dependencies::is_woocommerce_active()) {
	//Add methods if WooCommerce is active
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'woosheets_add_action_links');
	function woosheets_add_action_links( $links ) {
	 $mylinks = array(
	 '<a href="' . admin_url( 'admin.php?page=woosheets' ) . '">Settings</a>',
	 );
	return array_merge( $mylinks, $links );
}
}else {
	add_action('admin_notices', 'wc_woosheets_admin_notice');
	if (!function_exists('wc_woosheets_admin_notice')) {
		function wc_woosheets_admin_notice() {
			echo '<div class="notice error woosheets-error">
				<div class="woosheets-message-icon" style="padding: 10px 0px 23px 0px;">
				<img src="'.plugins_url().'/woosheets/images/woosheetslogo-notice.png" style="float:left;">
					<p style="padding-left: 64px;padding-top: 11px;">WooSheets plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!</p>
					</div>
			</div>';
		}
	}
}
