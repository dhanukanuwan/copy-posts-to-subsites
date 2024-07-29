<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://hashcodeab.se
 * @since      1.0.0
 *
 * @package    Copy_Wpmu_Posts
 * @subpackage Copy_Wpmu_Posts/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Copy_Wpmu_Posts
 * @subpackage Copy_Wpmu_Posts/public
 * @author     Dhanuka Gunarathna <dhanuka@hashcodeab.se>
 */
class Copy_Wpmu_Posts_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Adding hreflang tags to copied pages to represent the priginal page/post.
	 *
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_add_hreflang_tags() {

		$post_id = get_the_ID();

		if ( empty( $post_id ) ) {
			return;
		}

		$current_lng = get_locale();

		$copied_langs = get_post_meta( $post_id, 'copied_languages', true );

		if ( ! empty( $copied_langs ) && is_array( $copied_langs ) ) {
			foreach ( $copied_langs as $copied_lang ) {

				if ( ! is_array( $copied_lang ) || ! isset( $copied_lang['lng'] ) ) {
					return;
				}

				if ( $copied_lang['lng'] === $current_lng ) {
					continue;
				}

				printf( "<link rel='alternate' hreflang='%s' href='%s' />\n", esc_attr( $copied_lang['lng'] ), esc_url( $copied_lang['url'] ) );
			}
		}

		$original_post_data = get_post_meta( $post_id, 'original_post_data', true );

		if ( ! empty( $original_post_data ) && isset( $original_post_data['lng'] ) ) {
			printf( "<link rel='alternate' hreflang='%s' href='%s' />\n", esc_attr( $original_post_data['lng'] ), esc_url( $original_post_data['url'] ) );
		}
	}

	/**
	 * Rendering current site's language as site locale.
	 *
	 * @since    1.0.0
	 * @param   string $content .
	 */
	public function copy_wpmu_posts_rank_math_og_locale( $content ) {

		$current_site_lng = get_locale();

		if ( ! empty( $current_site_lng ) ) {
			$content = $current_site_lng;
		}

		return $content;
	}
}
