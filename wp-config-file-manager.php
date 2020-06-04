<?php
/**
 * Plugin Name: WP Config File Manager
 * Description: Enable or disable the debug log, update the memory limit in wp-debug.php file.
 * Plugin URI: https://profiles.wordpress.org/mahesh901122/
 * Author: Mahesh M. Waghmare
 * Author URI: https://maheshwaghmare.com/
 * Version: 1.0.0
 * License: GNU General Public License v2.0
 * Text Domain: wp-config-file-manager
 *
 * @package WP Config File Manager
 */

// Set constants.
define( 'WP_CONFIG_FILE_MANAGER_VER', '1.0.0' );
define( 'WP_CONFIG_FILE_MANAGER_FILE', __FILE__ );
define( 'WP_CONFIG_FILE_MANAGER_BASE', plugin_basename( WP_CONFIG_FILE_MANAGER_FILE ) );
define( 'WP_CONFIG_FILE_MANAGER_DIR', plugin_dir_path( WP_CONFIG_FILE_MANAGER_FILE ) );
define( 'WP_CONFIG_FILE_MANAGER_URI', plugins_url( '/', WP_CONFIG_FILE_MANAGER_FILE ) );

// Load WP Config Transformer.
if( ! class_exists( 'WPConfigTransformer' ) ) {
	require_once 'lib/wp-config-transformer/WPConfigTransformer.php';
}

require_once WP_CONFIG_FILE_MANAGER_DIR . 'classes/class-wp-config-file-manager.php';
