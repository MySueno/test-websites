<?php
/**
 * Plugin Name: TableMaster Pro
 * Plugin URI:  https://example.com/tablemaster-pro
 * Description: Maak krachtige, interactieve tabellen met groepering, sortering, filtering en paginering. Beheer via een intuïtief dashboard en publiceer via shortcode of Gutenberg block.
 * Version:     1.3.64
 * Author:      TableMaster Pro
 * Author URI:  https://example.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tablemaster-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TMP_VERSION',     '1.3.64' );
define( 'TMP_PLUGIN_FILE', __FILE__ );
define( 'TMP_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'TMP_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'TMP_TEXT_DOMAIN', 'tablemaster-pro' );

define( 'TMP_UPDATE_URL', 'https://table-importer-tool.replit.app/' );
define( 'TMP_SIGNING_PUBLIC_KEY', '671d355b4e1db480f8df36523bfed80b2dda95b8234a259e8f1ecac4d5a154bf' );

require_once TMP_PLUGIN_DIR . 'includes/class-db.php';
require_once TMP_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once TMP_PLUGIN_DIR . 'includes/class-ajax.php';
require_once TMP_PLUGIN_DIR . 'includes/class-wpml.php';
require_once TMP_PLUGIN_DIR . 'includes/class-settings.php';
require_once TMP_PLUGIN_DIR . 'includes/class-tablemaster.php';
require_once TMP_PLUGIN_DIR . 'admin/class-admin.php';
require_once TMP_PLUGIN_DIR . 'includes/class-block.php';
require_once TMP_PLUGIN_DIR . 'includes/class-elementor.php';
require_once TMP_PLUGIN_DIR . 'includes/class-updater.php';

register_activation_hook( __FILE__, array( 'TableMaster_DB', 'install' ) );

function tablemaster_pro_init() {
    $db_version = get_option( 'tablemaster_db_version', '0' );
    if ( version_compare( $db_version, TMP_VERSION, '<' ) ) {
        TableMaster_DB::install();
    }

    $plugin = new TableMaster();
    $plugin->run();

    $updater = new TableMaster_Updater();
    $updater->init();
}
add_action( 'plugins_loaded', 'tablemaster_pro_init' );
