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

		$default_post_types = array( 'page', 'post' );
		$allowed_post_types = apply_filters( 'copy_wpmu_allowed_post_types', $default_post_types );

		add_meta_box( 'copy_wpmu_pages_metabox', __( 'Copy to sub-site', 'copy-wpmu-posts' ), array( $this, 'copy_wpmu_posts_metabox_callback' ), $allowed_post_types, 'side', 'high' );
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

			$current_site_id  = get_current_blog_id();
			$current_site_lng = get_locale();
			$current_post_url = get_permalink( $post_id );

			if ( ! empty( $post_data ) && ! is_wp_error( $post_data ) ) {

				$acf_data     = get_fields( $post_id, false );
				$post_title   = $post_data->post_title;
				$post_content = $post_data->post_content;
				$post_type    = $post_data->post_type;
				$post_status  = $post_data->post_status;
				$post_name    = $post_data->post_name;

				$custom_permalink = get_post_meta( $post_id, 'custom_permalink', true );
				$page_template    = get_post_meta( $post_id, '_wp_page_template', true );

				$copied_languages = get_post_meta( $post_id, 'copied_languages', true );
				$target_site_lng  = '';

				if ( empty( $copied_languages ) ) {
					$copied_languages = array();
				}

				$post_terms = $this->copy_wpmu_get_post_terms( $post_id );

				switch_to_blog( $site_id );

				$target_site_lng = get_locale();

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
					} else {

						$this->copy_wpmu_insert_post_terms( $inserted_post_id, $post_terms );

					}

					if ( ! empty( $custom_permalink ) ) {
						update_post_meta( $inserted_post_id, 'custom_permalink', $custom_permalink );
					}

					update_post_meta( $inserted_post_id, 'original_post_id', $post_id );
					update_post_meta( $inserted_post_id, 'original_post_url', $current_post_url );
					update_post_meta( $inserted_post_id, 'original_site_id', $current_site_id );
					update_post_meta( $inserted_post_id, 'original_lng', $current_site_lng );

					$data['new_post_id'] = $inserted_post_id;
					$success             = true;
				}

				restore_current_blog();

				if ( true === $success && ! empty( $target_site_lng ) ) {

					if ( ! in_array( $target_site_lng, $copied_languages, true ) ) {
						$copied_languages[] = $target_site_lng;
					}

					update_post_meta( $inserted_post_id, 'copied_languages', $copied_languages );
				}
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

	/**
	 * Get all terms and taxonomies assigned to the post.
	 *
	 * @param    int $post_id .
	 * @since    1.0.0
	 */
	private function copy_wpmu_get_post_terms( $post_id ) {

		$post_terms = array();

		$post_taxonomies = get_post_taxonomies( $post_id );

		if ( ! empty( $post_taxonomies ) ) {
			foreach ( $post_taxonomies as $post_taxonomy ) {

				$taxonomy_terms = get_the_terms( $post_id, $post_taxonomy );

				if ( ! empty( $taxonomy_terms ) ) {

					foreach ( $taxonomy_terms as $key => $taxonomy_term ) {
						if ( $taxonomy_term->parent ) {

							$parent_term = get_term_by( 'id', $taxonomy_term->parent, $post_taxonomy );

							if ( ! empty( $parent_term ) ) {
								$taxonomy_terms[ $key ] = (object) array_merge( (array) $taxonomy_term, array( 'parent_term' => $parent_term ) );
							}
						}
					}

					$post_terms[] = array(
						'taxonomy' => $post_taxonomy,
						'terms'    => $taxonomy_terms,
					);
				}
			}
		}

		return $post_terms;
	}

	/**
	 * Insert terms to the post.
	 *
	 * @param    int   $post_id .
	 * @param    array $terms .
	 * @since    1.0.0
	 */
	private function copy_wpmu_insert_post_terms( $post_id, $terms ) {

		if ( empty( $post_id ) || empty( $terms ) ) {
			return;
		}

		foreach ( $terms as $term_data ) {

			$taxonomy_name = $term_data['taxonomy'];

			if ( isset( $term_data['terms'] ) && ! empty( $term_data['terms'] ) ) {

				$term_ids = array();

				foreach ( $term_data['terms'] as $term ) {

					$found_term = term_exists( $term->slug, $taxonomy_name );

					if ( 0 !== $found_term && null !== $found_term ) {
						$term_ids[] = (int) $found_term['term_id'];
					} else {

						$new_term = $this->copy_wpmu_insert_taxonomy_term( $term );

						if ( ! empty( $new_term ) && isset( $new_term['term_id'] ) ) {
							$term_ids[] = (int) $new_term['term_id'];
						}
					}
				}

				wp_set_post_terms( $post_id, $term_ids, $taxonomy_name );
			}
		}
	}

	/**
	 * Insert terms to the taxonomy.
	 *
	 * @param    WP_Term $term_data .
	 * @since    1.0.0
	 */
	private function copy_wpmu_insert_taxonomy_term( $term_data ) {

		if ( empty( $term_data ) ) {
			return;
		}

		$parent_id = 0;

		// Insert the parent term first if found.
		if ( $term_data->parent && $term_data->parent_term ) {

			$found_parent = term_exists( $term_data->parent_term->slug, $term_data->taxonomy );

			// Found the term in destination site.
			if ( 0 !== $found_parent && null !== $found_parent ) {
				$parent_id = $found_parent['term_id'];
			} else {

				$new_parent = wp_insert_term(
					$term_data->parent_term->name,
					$term_data->taxonomy,
					array(
						'description' => $term_data->parent_term->description,
						'slug'        => $term_data->parent_term->slug,
					)
				);

				if ( ! empty( $new_parent ) && isset( $new_parent['term_id'] ) ) {
					$parent_id = (int) $new_parent['term_id'];
				}
			}
		}

		// Insert the new term with parent.
		$new_term = wp_insert_term(
			$term_data->name,
			$term_data->taxonomy,
			array(
				'description' => $term_data->description,
				'slug'        => $term_data->slug,
				'parent'      => $parent_id,
			)
		);

		return $new_term;
	}

	/**
	 * Enable post copy meta box in custom post types.
	 *
	 * @param    array $allowed_post_types default post types.
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_enable_custom_post_types( $allowed_post_types ) {

		if ( ! is_array( $allowed_post_types ) || ! $allowed_post_types ) {
			$allowed_post_types = array();
		}

		$post_types = array( 'article', 'game', 'operator', 'event', 'gameplay', 'rg_content_block', 'operator_offers' );

		$allowed_post_types = array_merge( $allowed_post_types, $post_types );

		return $allowed_post_types;
	}
}
