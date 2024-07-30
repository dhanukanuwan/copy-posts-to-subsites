<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://hashcodeab.se
 * @since             1.0.0
 * @package           Copy_Wpmu_Posts
 *
 * @wordpress-plugin
 * Plugin Name:       Copy Multisite Posts
 * Plugin URI:        https://hashcodeab.se
 * Description:       Copy posts and pages between sub-sites in the WordPress multisite network.
 * Version:           1.0.0
 * Author:            Dhanuka Gunarathna
 * Author URI:        https://hashcodeab.se/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       copy-wpmu-posts
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'COPY_WPMU_POSTS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-copy-wpmu-posts-activator.php
 */
function activate_copy_wpmu_posts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-copy-wpmu-posts-activator.php';
	Copy_Wpmu_Posts_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-copy-wpmu-posts-deactivator.php
 */
function deactivate_copy_wpmu_posts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-copy-wpmu-posts-deactivator.php';
	Copy_Wpmu_Posts_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_copy_wpmu_posts' );
register_deactivation_hook( __FILE__, 'deactivate_copy_wpmu_posts' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-copy-wpmu-posts.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_copy_wpmu_posts() {

	$plugin = new Copy_Wpmu_Posts();
	$plugin->run();
}

run_copy_wpmu_posts();
