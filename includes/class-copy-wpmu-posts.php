<?php
/**
 * The file that defines the core plugin class
 *
 * @link       https://hashcodeab.se
 * @since      1.0.0
 *
 * @package    Copy_Wpmu_Posts
 * @subpackage Copy_Wpmu_Posts/includes
 */

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Copy_Wpmu_Posts
 * @subpackage Copy_Wpmu_Posts/includes
 * @author     Dhanuka Gunarathna <dhanuka@hashcodeab.se>
 */
class Copy_Wpmu_Posts {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Copy_Wpmu_Posts_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'COPY_WPMU_POSTS_VERSION' ) ) {
			$this->version = COPY_WPMU_POSTS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'copy-wpmu-posts';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Copy_Wpmu_Posts_Loader. Orchestrates the hooks of the plugin.
	 * - Copy_Wpmu_Posts_i18n. Defines internationalization functionality.
	 * - Copy_Wpmu_Posts_Admin. Defines all hooks for the admin area.
	 * - Copy_Wpmu_Posts_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-copy-wpmu-posts-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-copy-wpmu-posts-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-copy-wpmu-posts-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-copy-wpmu-posts-public.php';

		$this->loader = new Copy_Wpmu_Posts_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Copy_Wpmu_Posts_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Copy_Wpmu_Posts_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Copy_Wpmu_Posts_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'copy_wpmu_posts_metabox' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'copy_wpmu_posts_metabox_enqueue_scripts' );
		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'copy_wpmu_posts_get_sites_endpoint' );
		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'copy_wpmu_posts_copy_to_subsite_endpoint' );
		$this->loader->add_action( 'copy_wpmu_posts_after_copy_to_destination', $plugin_admin, 'copy_wpmu_posts_copy_gameplay_page_data', 10, 2 );

		$this->loader->add_filter( 'copy_wpmu_allowed_post_types', $plugin_admin, 'copy_wpmu_posts_enable_custom_post_types', 10, 1 );
		$this->loader->add_filter( 'copy_wpmu_posts_before_copy_to_destination', $plugin_admin, 'copy_wpmu_posts_handle_gameplay_pages', 10, 3 );
		$this->loader->add_filter( 'copy_wpmu_posts_before_copy_to_destination', $plugin_admin, 'copy_wpmu_posts_handle_reorder_data', 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Copy_Wpmu_Posts_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_head', $plugin_public, 'copy_wpmu_posts_add_hreflang_tags', 1 );

		$this->loader->add_filter( 'rank_math/opengraph/facebook/og_locale', $plugin_public, 'copy_wpmu_posts_rank_math_og_locale' );
		$this->loader->add_filter( 'acf/format_value', $plugin_public, 'copy_wpmu_posts_acf_image_fallback', 10, 3 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {

		if ( ! is_multisite() ) {
			return;
		}

		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Copy_Wpmu_Posts_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
