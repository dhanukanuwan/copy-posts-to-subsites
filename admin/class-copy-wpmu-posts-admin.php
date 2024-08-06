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
				$target_site_link = '';

				if ( empty( $copied_languages ) ) {
					$copied_languages = array();
				}

				$post_terms = $this->copy_wpmu_get_post_terms( $post_id );

				$post_args = array(
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'post_type'    => $post_type,
					'post_name'    => $post_name,
				);

				$pre_copy_data = apply_filters( 'copy_wpmu_posts_before_copy_to_destination', $post_args, $post_id, $site_id );

				switch_to_blog( $site_id );

				$target_site_lng = get_option( 'WPLANG' );

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

					do_action( 'copy_wpmu_posts_after_copy_to_destination', $pre_copy_data, $inserted_post_id );

					$target_site_link = get_permalink( $inserted_post_id );

					if ( ! empty( $acf_data ) ) {

						foreach ( $acf_data as $key => $value ) {
							update_field( $key, $value, $inserted_post_id );
						}
					}

					if ( 'game' === $post_type ) {

						$game_data = array(
							'original_game' => $post_id,
							'new_game'      => $inserted_post_id,
						);
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

					$original_post_data = array(
						'post_id' => $post_id,
						'url'     => $current_post_url,
						'site_id' => $current_site_id,
						'lng'     => $current_site_lng,
					);

					update_post_meta( $inserted_post_id, 'original_post_data', $original_post_data );

					$data['new_post_id'] = $inserted_post_id;
					$success             = true;
				}

				restore_current_blog();

				if ( true === $success && ! empty( $target_site_lng ) ) {

					$item_key = array_search( $target_site_lng, array_column( $copied_languages, 'lng' ), true );

					if ( false === $item_key ) {
						$copied_languages[] = array(
							'lng' => $target_site_lng,
							'url' => $target_site_link,
						);
					}

					$data['target_url'] = $target_site_link;

					update_post_meta( $post_id, 'copied_languages', $copied_languages );
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

						$term_acf_fields = get_fields( $post_taxonomy . '_' . $taxonomy_term->term_id );

						if ( ! empty( $term_acf_fields ) && ! is_wp_error( $term_acf_fields ) ) {
							$taxonomy_terms[ $key ] = (object) array_merge( (array) $taxonomy_term, array( 'term_acf_fields' => $term_acf_fields ) );
						}

						if ( $taxonomy_term->parent ) {

							$parent_term = get_term_by( 'id', $taxonomy_term->parent, $post_taxonomy );

							if ( ! empty( $parent_term ) ) {

								$parent_acf_fields = get_fields( $post_taxonomy . '_' . $parent_term->term_id );

								if ( ! empty( $parent_acf_fields ) && ! is_wp_error( $parent_acf_fields ) ) {
									$parent_term = (object) array_merge( (array) $parent_term, array( 'term_acf_fields' => $parent_acf_fields ) );
								}

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

					if ( isset( $term_data->parent_term->term_acf_fields ) && ! empty( $term_data->parent_term->term_acf_fields ) ) {
						foreach ( $term_data->parent_term->term_acf_fields as $term_acf_key => $term_acf_value ) {
							update_field( $term_acf_key, $term_acf_value, $term_data->taxonomy . '_' . $new_parent['term_id'] );
						}
					}
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

		if ( ! empty( $new_term ) && ! is_wp_error( $new_term ) ) {
			if ( isset( $term_data->term_acf_fields ) && ! empty( $term_data->term_acf_fields ) ) {
				foreach ( $term_data->term_acf_fields as $term_acf_key => $term_acf_value ) {
					update_field( $term_acf_key, $term_acf_value, $term_data->taxonomy . '_' . $new_term['term_id'] );
				}
			}
		}

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

	/**
	 * Automaticaaly copy play page belong to the game.
	 *
	 * @param    int   $post_data .
	 * @param    array $post_id .
	 * @param    int   $destination_site .
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_handle_gameplay_pages( $post_data, $post_id, $destination_site ) {

		if ( empty( $post_id ) || empty( $post_data ) || empty( $destination_site ) ) {
			return $post_data;
		}

		if ( 'game' !== $post_data['post_type'] ) {
			return $post_data;
		}

		$play_page_data = $this->copy_wpmu_posts_get_gameplay_page_data( $post_id );

		if ( empty( $play_page_data ) ) {
			return $post_data;
		}

		$post_data['play_page_data'] = $play_page_data;

		return $post_data;
	}

	/**
	 * Get play page data belong to a game.
	 *
	 * @param    int $game_id .
	 * @since    1.0.0
	 */
	private function copy_wpmu_posts_get_gameplay_page_data( $game_id ) {

		$play_page_data = array();

		if ( empty( $game_id ) ) {
			return $play_page_data;
		}

		$play_page_id = get_post_meta( $game_id, 'game_play_page_id', true );

		if ( empty( $play_page_id ) ) {
			return $play_page_data;
		}

		$play_page = get_post( $play_page_id );

		if ( empty( $play_page ) || is_wp_error( $play_page ) ) {
			return $play_page_data;
		}

		$play_page_data = array(
			'id'      => $play_page_id,
			'title'   => $play_page->post_title,
			'slug'    => $play_page->post_name,
			'game_id' => $game_id,
		);

		$play_meta_keys = array(
			'play_page_title_two',
			'_yoast_wpseo_title',
			'_yoast_wpseo_metadesc',
		);

		foreach ( $play_meta_keys as $play_meta_key ) {

			$play_meta_val = get_post_meta( $play_page_id, $play_meta_key, true );

			if ( ! empty( $play_meta_val ) ) {
				$play_page_data[ $play_meta_key ] = $play_meta_val;
			}
		}

		$offers = get_field( 'offers' );

		if ( ! empty( $offers ) ) {
			$play_page_data['offers'] = $offers;
		}

		return $play_page_data;
	}

	/**
	 * Copy play page data belong to a game.
	 *
	 * @param    array $pre_copy_data .
	 * @param    int   $new_post_id .
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_copy_gameplay_page_data( $pre_copy_data, $new_post_id ) {

		if ( ! isset( $pre_copy_data['play_page_data'] ) || empty( $new_post_id ) ) {
			return;
		}

		if ( 'game' !== $pre_copy_data['post_type'] ) {
			return;
		}

		$play_page_data = $pre_copy_data['play_page_data'];

		$page_data = array(
			'post_title'   => wp_strip_all_tags( $play_page_data['title'] ),
			'post_name'    => $play_page_data['slug'],
			'post_content' => ' ',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'gameplay',
			'meta_input'   => array(
				'belong_game_id' => $new_post_id,
			),
		);

		$play_page_id = wp_insert_post( $page_data );

		if ( ! empty( $play_page_id ) && ! is_wp_error( $play_page_id ) ) {

			update_post_meta( $new_post_id, 'game_play_page_id', $play_page_id );

			$play_meta_keys = array(
				'play_page_title_two',
				'_yoast_wpseo_title',
				'_yoast_wpseo_metadesc',
			);

			foreach ( $play_meta_keys as $play_meta_key ) {
				if ( isset( $play_page_data[ $play_meta_key ] ) ) {
					update_post_meta( $play_page_id, $play_meta_key, $play_page_data[ $play_meta_key ] );
				}
			}

			if ( isset( $play_page_data['offers'] ) ) {
				update_field( 'offers', $play_page_data['offers'], $play_page_id );
			}
		}
	}

	/**
	 * Automaticaaly copy game and operator custom re-order data.
	 *
	 * @param    int   $post_data .
	 * @param    array $post_id .
	 * @param    int   $destination_site .
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_handle_reorder_data( $post_data, $post_id, $destination_site ) {

		if ( empty( $post_id ) || empty( $post_data ) || empty( $destination_site ) ) {
			return $post_data;
		}

		$allowed_post_types = array( 'game', 'operator' );

		if ( ! in_array( $post_data['post_type'], $allowed_post_types, true ) ) {
			return $post_data;
		}

		$reorder_data = array();
		$reorder_keys = array(
			'roger_post_order',
			'roger_post_order_gb',
			'roger_post_order_ca',
			'roger_post_order_rest',
		);

		$game_categories = wp_get_post_terms( $post_id, 'game_category' );

		if ( ! empty( $game_categories ) && ! is_wp_error( $game_categories ) ) {
			foreach ( $game_categories as $game_category ) {

				$category_slug = str_replace( '_', '-', $game_category->slug );

				$gb_key   = 'roger_' . $category_slug . '_order_gb';
				$ca_key   = 'roger_' . $category_slug . '_order_ca';
				$rest_key = 'roger_' . $category_slug . '_order_rest';

				$reorder_keys[] = $gb_key;
				$reorder_keys[] = $ca_key;
				$reorder_keys[] = $rest_key;

			}
		}

		foreach ( $reorder_keys as $reorder_key ) {

			$meta_value = get_post_meta( $post_id, $reorder_key, true );

			if ( ! empty( $meta_value ) ) {
				$reorder_data[ $reorder_key ] = $meta_value;
			}
		}

		if ( ! empty( $reorder_data ) ) {
			$post_data['game_operator_meta_data'] = $reorder_data;
		}

		return $post_data;
	}

	/**
	 * Automaticaaly copy game and operator custom re-order data.
	 *
	 * @param    array $pre_copy_data .
	 * @param    int   $new_post_id .
	 * @since    1.0.0
	 */
	public function copy_wpmu_posts_copy_reorder_data( $pre_copy_data, $new_post_id ) {

		if ( ! isset( $pre_copy_data['game_operator_meta_data'] ) || empty( $new_post_id ) ) {
			return;
		}

		$allowed_post_types = array( 'game', 'operator' );

		if ( ! in_array( $pre_copy_data['post_type'], $allowed_post_types, true ) ) {
			return;
		}

		$meta_data = $pre_copy_data['game_operator_meta_data'];

		if ( ! empty( $meta_data ) ) {
			foreach ( $meta_data as $meta_key => $meta_val ) {
				update_post_meta( $new_post_id, $meta_key, $meta_val );
			}
		}
	}
}
