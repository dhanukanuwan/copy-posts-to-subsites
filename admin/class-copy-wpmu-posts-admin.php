<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://hashcodeab.se
 * @since      1.0.0
 *
 * @package    Copy_Wpmu_Posts
 * @subpackage Copy_Wpmu_Posts/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Copy_Wpmu_Posts
 * @subpackage Copy_Wpmu_Posts/admin
 * @author     Dhanuka Gunarathna <dhanuka@hashcodeab.se>
 */
class Copy_Wpmu_Posts_Admin {

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
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_metabox_enqueue_scripts() {

		$dependancies = array( 'react', 'react-jsx-runtime', 'wp-element' );
		$version      = $this->version;

		$deps_file = plugin_dir_path( __FILE__ ) . 'partials/metaboxapp/build/index.asset.php';

		if ( file_exists( $deps_file ) ) {
			$deps_file    = require $deps_file;
			$dependancies = $deps_file['dependencies'];
			$version      = $deps_file['version'];
		}

		wp_enqueue_script( 'copy_wpmu_posts_metabox-js', plugin_dir_url( __FILE__ ) . 'partials/metaboxapp/build/index.js', $dependancies, $version, true );

		$copy_wpmu_js_data = array(
			'rest_root'  => esc_url_raw( rest_url() ),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'post_id'    => get_the_ID(),
		);

		wp_localize_script( 'copy_wpmu_posts_metabox-js', 'copy_wpmu_js_data', $copy_wpmu_js_data );
	}

	/**
	 * Register product access meta box.
	 *
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_metabox() {
		add_meta_box( 'copy_wpmu_pages_metabox', __( 'Copy to sub-site', 'copy-wpmu-posts' ), array( $this, 'copy_wpmu_posts_metabox_callback' ), 'page', 'side', 'high' );
	}

	/**
	 * Product access metabox callback.
	 *
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_metabox_callback() {

		include plugin_dir_path( __DIR__ ) . 'admin/partials/copy-wpmu-posts-metabox-render.php';
	}

	/**
	 * Get all subsites.
	 *
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_get_sites_endpoint() {
		register_rest_route(
			'copywpmuposts/v1',
			'/getsubsites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'copy_wpmu_posts_get_sites_endpoint_callback' ),
					'permission_callback' => array( $this, 'copy_wpmu_posts_rest_api_user_permissions' ),
				),
			)
		);
	}

	/**
	 * Add new organization callback.
	 *
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_get_sites_endpoint_callback() {

		$data    = array();
		$success = false;
		$message = '';

		$args = array(
			'public'       => 1,
			'archived'     => 0,
			'mature'       => 0,
			'spam'         => 0,
			'deleted'      => 0,
			'site__not_in' => get_current_blog_id(),
		);

		$sites = get_sites( $args );

		if ( ! empty( $sites ) && ! is_wp_error( $sites ) ) {
			$data    = $sites;
			$success = true;
		}

		$response = rest_ensure_response(
			array(
				'data'    => $data,
				'success' => $success,
				'message' => $message,
			)
		);

		return $response;
	}

	/**
	 * Copy data to subsite.
	 *
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_copy_to_subsite_endpoint() {
		register_rest_route(
			'copywpmuposts/v1',
			'/copytosubsite',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'copy_wpmu_posts_copy_to_subsite_endpoint_callback' ),
					'args'                => array(
						'post_id' => array(
							'required' => true,
							'type'     => 'number',
						),
						'site_id' => array(
							'required' => true,
							'type'     => 'number',
						),
					),
					'permission_callback' => array( $this, 'copy_wpmu_posts_rest_api_user_permissions' ),
				),
			)
		);
	}

	/**
	 * Copy post data to subsite callback.
	 *
	 * @param    array $request request array.
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_copy_to_subsite_endpoint_callback( $request ) {

		$post_id = sanitize_text_field( $request->get_param( 'post_id' ) );
		$site_id = sanitize_text_field( $request->get_param( 'site_id' ) );

		$data    = array();
		$success = false;
		$message = '';

		if ( ! empty( $post_id ) && ! empty( $site_id ) ) {

			$post_data = get_post( $post_id );

			if ( ! empty( $post_data ) && ! is_wp_error( $post_data ) ) {

				$acf_data     = get_fields( $post_id, false );
				$post_title   = $post_data->post_title;
				$post_content = $post_data->post_content;
				$post_type    = $post_data->post_type;
				$post_status  = $post_data->post_status;
				$post_name    = $post_data->post_name;

				$custom_permalink = get_post_meta( $post_id, 'custom_permalink', true );
				$page_template    = get_post_meta( $post_id, '_wp_page_template', true );

				switch_to_blog( $site_id );

				$new_post_args = array(
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'post_type'    => $post_type,
					'post_author'  => get_current_user_id(),
					'post_status'  => $post_status,
					'post_name'    => $post_name,
				);

				$inserted_post_id = wp_insert_post( $new_post_args );

				if ( ! empty( $inserted_post_id ) && ! is_wp_error( $inserted_post_id ) ) {

					if ( ! empty( $acf_data ) ) {

						foreach ( $acf_data as $key => $value ) {
							update_field( $key, $value, $inserted_post_id );
						}
					}

					if ( 'page' === $post_type ) {

						if ( ! empty( $page_template ) ) {
							update_post_meta( $inserted_post_id, '_wp_page_template', $page_template );
						}

						if ( is_front_page( $post_id ) ) {
							update_option( 'page_on_front', $inserted_post_id );
							update_option( 'show_on_front', 'page' );
						}
					}

					if ( ! empty( $custom_permalink ) ) {
						update_post_meta( $inserted_post_id, 'custom_permalink', $custom_permalink );
					}

					update_post_meta( $inserted_post_id, 'original_post_id', $post_id );

					$data['new_post_id'] = $inserted_post_id;
					$success             = true;
				}

				restore_current_blog();

			}
		}

		$response = rest_ensure_response(
			array(
				'data'    => $data,
				'success' => $success,
				'message' => $message,
			)
		);

		return $response;
	}

	/**
	 * Check user permissions.
	 *
	 * @param    array $request request array.
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_rest_api_user_permissions( $request ) { //phpcs:ignore
		return current_user_can( 'manage_options' );
	}
}
