<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://hashcodeab.se
 * @since      1.0.0
 *
 * @package    Copy_Wpmu_Posts
 * @subpackage Copy_Wpmu_Posts/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Copy_Wpmu_Posts
 * @subpackage Copy_Wpmu_Posts/includes
 * @author     Dhanuka Gunarathna <dhanuka@hashcodeab.se>
 */
class Copy_Wpmu_Posts_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'copy-wpmu-posts',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
