<?php

namespace ZionBuilder;

use ZionBuilder\Plugin;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Class Cache
 *
 * @package ZionBuilder
 */
class Cache {
	/**
	 * Holds the name of the directory to use by default for assets config
	 */
	const CACHE_FOLDER_NAME = 'cache';

	/**
	 * Holds the name of the dynamic cache file name
	 */
	const DYNAMIC_CSS_FILENAME = 'dynamic_css.css';

	/**
	 * Holds a reference to the cache folder
	 *
	 * @var array{path: string, url: string}
	 */
	private $cache_directory_config = null;

	/**
	 * Holds a reference to all post ids that we need to enqueue styles
	 *
	 * @var array<int>
	 */
	private $registered_post_ids = [];

	private static $loaded_assets            = [];
	private static $loaded_javascript_assets = [];
	private static $done_areas_css           = [];
	private static $done_areas_js            = [];
	private static $late_scripts             = [];

	private $areas_raw_css = [];
	private $active_areas  = [];

	/**
	 * Main class constructor
	 */
	public function __construct() {
		// Delete cache for posts actions
		add_action( 'delete_post', [ $this, 'delete_post_cache' ] );
		add_action( 'save_post', [ $this, 'delete_post_cache' ] );

		// Delete all cache
		add_action( 'after_switch_theme', [ $this, 'delete_all_cache' ] );
		add_action( 'activated_plugin', [ $this, 'delete_all_cache' ] );
		add_action( 'zionbuilder/settings/save', [ $this, 'delete_all_cache' ] );

		// Enqueue styles
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'register_default_scripts' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'on_enqueue_scripts' ] );
			add_action( 'wp_footer', [ $this, 'late_enqueue_styles' ] );

		} else {
			// Register default scripts so we can use them in edit mode
			add_action( 'zionbuilder/editor/before_scripts', [ $this, 'register_default_scripts' ] );
		}
	}

	/**
	 * Enqueues page scripts
	 *
	 * @return void
	 */
	public function on_enqueue_scripts() {
		$this->enqueue_post_styles();
		$this->enqueue_post_scripts();
		$this->enqueue_dynamic_css();
	}

	/**
	 * Create the css files after the page is rendered so we have access to all areas
	 *
	 * @return void
	 */
	public function late_enqueue_styles() {
		foreach ( self::$late_scripts as $post_id ) {
			$this->set_active_area( $post_id );
			$this->compile_and_include_css_cache_file_for_post( $post_id );
		}
	}

	/**
	 * Returns true if we need to generate CSS for elements
	 *
	 * @return boolean
	 */
	public function should_generate_css() {
		return in_array( $this->get_active_area(), self::$late_scripts, true );
	}

	public function get_active_area() {
		return end( $this->active_areas );
	}

	public function set_active_area( $post_id ) {
		$this->active_areas[] = $post_id;
	}

	public function reset_active_area() {
		array_pop( $this->active_areas );
	}

	public function add_raw_css( $css ) {
		if ( ! isset( $this->areas_raw_css[$this->get_active_area()] ) ) {
			$this->areas_raw_css[$this->get_active_area()] = '';
		}

		$this->areas_raw_css[$this->get_active_area()] .= $css;
	}

	public function compile_and_include_css_cache_file_for_post( $post_id ) {
		$this->compile_css_cache_file_for_post( $post_id );
		$cache_file_config = $this->get_cache_file_config( $post_id );
		wp_enqueue_style( $cache_file_config['handle'], $cache_file_config['url'], [], $this->get_cache_version( $post_id ) );
	}

	/**
	 * Register plugin default scripts
	 *
	 * @return void
	 */
	public function register_default_scripts() {
		// register styles
		wp_register_style( 'swiper', Utils::get_file_url( 'assets/vendors/swiper/swiper.min.css' ), [], Plugin::instance()->get_version() );

		// Load animations
		wp_register_style( 'zion-frontend-animations', plugins_url( 'zionbuilder/assets/vendors/css/animate.css' ), [], Plugin::instance()->get_version() );

		// Register scripts
		wp_register_script( 'zb-modal', Utils::get_file_url( 'assets/vendors/js/modal.min.js' ), [], Plugin::instance()->get_version(), true );
		// Video
		wp_register_script( 'zb-video', Utils::get_file_url( 'assets/vendors/js/ZBVideo.js' ), [], Plugin::instance()->get_version(), true );
		wp_register_script( 'zb-video-bg', Utils::get_file_url( 'assets/vendors/js/ZBVideoBg.js' ), [ 'zb-video' ], Plugin::instance()->get_version(), true );

		// Swiper slider
		wp_register_script( 'swiper', Utils::get_file_url( 'assets/vendors/swiper/swiper.min.js' ), [], Plugin::instance()->get_version(), true );

		// Animate JS
		wp_register_script( 'zionbuilder-animatejs', Utils::get_file_url( 'dist/js/animateJS.js' ), [], Plugin::instance()->get_version(), true );
		wp_add_inline_script( 'zionbuilder-animatejs', 'animateJS()' );
	}

	/**
	 * Register Post Id
	 *
	 * Will register post ids for which we need to enqueue styles
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function register_post_id( $post_id ) {
		$this->registered_post_ids[] = absint( $post_id );
	}

	/**
	 * Unregister Post Id
	 *
	 * Will remove a post id from the list of post ids for which we need to enqueue styles
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function unregister_post_id( $post_id ) {
		$post_id = absint( $post_id );
		unset( $this->registered_post_ids[$post_id] );
	}

	/**
	 * Enqueue dynamic css file
	 *
	 * @return void
	 */
	public function enqueue_dynamic_css() {
		$cache_directory        = $this->get_cache_directory();
		$dynamic_cache_file     = $cache_directory['path'] . self::DYNAMIC_CSS_FILENAME;
		$dynamic_cache_file_url = $cache_directory['url'] . self::DYNAMIC_CSS_FILENAME;

		// Create the file if it doesn't exists
		if ( ! is_file( $dynamic_cache_file ) || Environment::is_debug() ) {
			$this->compile_dynamic_css();
		}

		$version = (string) filemtime( $dynamic_cache_file );
		wp_enqueue_style( 'znpb-dynamic-css', $dynamic_cache_file_url, [], $version );
	}

	/**
	 * Enqueue Post Styles
	 *
	 * Will enqueue the cached styles for all registered post ids
	 *
	 * @return void
	 */
	public function enqueue_post_styles() {
		$active_post_id = Plugin::$instance->post_manager->get_active_post_id();

		// Enqueue element styles
		$this->enqueue_elements_styles();

		foreach ( $this->registered_post_ids as $post_id ) {
			$file_config = $this->get_cache_file_config( $post_id );

			// Only load the scripts that are not autogenerated. This is needed so that we can
			// load theme builder header/footer styles when editing a page
			if ( Plugin::$instance->editor->preview->is_preview_mode() && $active_post_id === $post_id ) {
				continue;
			}

			if ( wp_script_is( $file_config['handle'], 'enqueued' ) ) {
				continue;
			}

			// Create the file if it doesn't exists
			if ( ! is_file( $file_config['path'] ) || Environment::is_debug() ) {
				self::$late_scripts[] = $post_id;
			} else {
				wp_enqueue_style( $file_config['handle'], $file_config['url'], [], $this->get_cache_version( $post_id ) );
			}
		}
	}


	/**
	 * Enqueue post scripts
	 *
	 * @return void
	 */
	public function enqueue_post_scripts() {
		$active_post_id = Plugin::$instance->post_manager->get_active_post_id();

		// Enqueue element scripts
		$this->enqueue_elements_scripts();

		foreach ( $this->registered_post_ids as $post_id ) {
			// Only load the scripts that are not autogenerated. This is needed so that we can
			// load theme builder header/footer styles when editing a page
			if ( Plugin::$instance->editor->preview->is_preview_mode() && $active_post_id === $post_id ) {
				continue;
			}

			$file_config = $this->get_cache_file_config( $post_id, 'js' );

			// Create the file if it doesn't exists
			if ( ! is_file( $file_config['path'] ) || Environment::is_debug() ) {
				$this->compile_js_cache_file_for_post( $post_id );
			}

			wp_enqueue_script( $file_config['handle'], $file_config['url'], [ 'jquery' ], $this->get_cache_version( $post_id ), true );
		}
	}

	/**
	 * Enqueue element styles
	 *
	 * @return void
	 */
	public function enqueue_elements_styles() {
		$elements_instances = Plugin::$instance->renderer->get_elements_instances();
		foreach ( $elements_instances as $element_instance ) {
			// Add the style.css file if present
			$element_instance->do_enqueue_styles();
		}
	}

	/**
	 * Enqueue element scripts
	 *
	 * @return void
	 */
	public function enqueue_elements_scripts() {
		$elements_instances = Plugin::$instance->renderer->get_elements_instances();
		foreach ( $elements_instances as $element_instance ) {
			$element_instance->do_enqueue_scripts();
		}
	}

	/**
	 * Get Cache Directory
	 *
	 * Returns the cache directory config
	 *
	 * @return array{path: string, url: string} An array containing cache directory path and url
	 */
	private function get_cache_directory() {
		if ( null === $this->cache_directory_config ) {
			$relative_cache_path       = trailingslashit( self::CACHE_FOLDER_NAME );
			$zionbuilder_upload_folder = FileSystem::get_zionbuilder_upload_dir();

			$this->cache_directory_config = [
				'path' => $zionbuilder_upload_folder['basedir'] . $relative_cache_path,
				'url'  => esc_url( set_url_scheme( $zionbuilder_upload_folder['baseurl'] . $relative_cache_path ) ),
			];

			// Create the cache folder
			wp_mkdir_p( $this->cache_directory_config['path'] );
		}

		return $this->cache_directory_config;
	}

	/**
	 * Get Cache File Config
	 *
	 * Returns the cache folder config for a given post id
	 *
	 * @param int   $post_id
	 * @param mixed $type
	 *
	 * @return array{file_name: string, handle: string, path: string, url: string } The cache file paths
	 */
	public function get_cache_file_config( $post_id, $type = 'css' ) {
		$post_id         = absint( $post_id );
		$file_name       = $post_id . '-layout.' . $type;
		$cache_directory = $this->get_cache_directory();

		return [
			'file_name' => $file_name,
			'handle'    => $post_id . '-layout-' . $type,
			'path'      => $cache_directory['path'] . $file_name,
			'url'       => esc_url( $cache_directory['url'] . $file_name ),
		];
	}

	/**
	 * Compile cache file for post
	 *
	 * Will create the cached css file containing the css code that is needed for a specific post id
	 *
	 * @param int $post_id
	 *
	 * @return boolean
	 */
	private function compile_css_cache_file_for_post( $post_id ) {
		$areas               = Plugin::$instance->renderer->get_registered_areas();
		self::$loaded_assets = [];
		$css                 = '';

		// Add the raw css
		$css = $this->areas_raw_css[$this->get_active_area()];

		if ( isset( $areas[$post_id] ) && is_array( $areas[$post_id] ) ) {
			foreach ( $areas[$post_id] as $element ) {
				$element_instance = Plugin::$instance->renderer->get_element_instance( $element['uid'] );

				if ( $element_instance ) {
					$css .= $this->get_css_for_element( $element_instance );
				}
			}
		}

		$css = apply_filters( 'zionbuilder/cache/page_css', $css, $post_id );

		// Minify the css
		$css               = $this->minify( $css );
		$cache_file_config = $this->get_cache_file_config( $post_id );

		return FileSystem::get_file_system()->put_contents( $cache_file_config['path'], $css, 0644 );
	}

	public function get_css_for_element( $element_instance ) {
		$css = '';

		$element_type = $element_instance->get_type();

		if ( ! isset( self::$loaded_assets[$element_type] ) ) {
			$element_styles = $element_instance->get_element_styles();

			foreach ( $element_styles as $style_url ) {
				$style_path = Utils::get_file_path_from_url( $style_url );
				$css       .= FileSystem::get_file_system()->get_contents( $style_path );
			}

			self::$loaded_assets[$element_type] = true;
		}

		// Check for children
		$childs = $element_instance->get_children();

		if ( is_array( $childs ) ) {
			foreach ( $childs as $element ) {
				$child_element_instance = Plugin::$instance->renderer->get_element_instance( $element['uid'] );
				if ( $child_element_instance ) {
					$css .= $this->get_css_for_element( $child_element_instance );
				}
			}
		}

		return $css;
	}

	/**
	 * Compile cache file for post
	 *
	 * Will create the cached css file containing the css code that is needed for a specific post id
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	private function compile_js_cache_file_for_post( $post_id ) {
		$js = '';

		// Check if we already parsed this area
		if ( in_array( $post_id, self::$done_areas_js, true ) ) {
			return '';
		}

		self::$done_areas_js[] = $post_id;

		// Reset the loaded scripts in case we have multiple scripts on the same page
		self::$loaded_javascript_assets = [];

		$areas = Plugin::$instance->renderer->get_registered_areas();

		if ( isset( $areas[$post_id] ) && is_array( $areas[$post_id] ) ) {
			foreach ( $areas[$post_id] as $element ) {
				$element_instance = Plugin::$instance->renderer->get_element_instance( $element['uid'] );
				$js              .= $this->get_javascript_for_element( $element_instance );
			}
		}

		$js                = apply_filters( 'zionbuilder/cache/page_js', $js, $post_id );
		$cache_file_config = $this->get_cache_file_config( $post_id, 'js' );

		$final_script = '';

		if ( ! empty( $js ) ) {
			$final_script = sprintf(
				'
			(function($) {
				window.ZionBuilderFrontend = {
					scripts: {},
					registerScript: function (scriptId, scriptCallback) {
						this.scripts[scriptId] = scriptCallback;
					},
					getScript(scriptId) {
						return this.scripts[scriptId]
					},
					unregisterScript: function(scriptId) {
						delete this.scripts[scriptId];
					},
					run: function() {
						var that = this;
						var $scope = $(document)
						Object.keys(this.scripts).forEach(function(scriptId) {
							var scriptObject = that.scripts[scriptId];
							scriptObject.run( $scope );
						})
					}
				};

				%s

				window.ZionBuilderFrontend.run();

			})(jQuery);
			',
				$js
			);
		}

		// Minify the js
		return FileSystem::get_file_system()->put_contents( $cache_file_config['path'], $final_script, 0644 );
	}

	private function get_javascript_for_element( $element_instance ) {
		$js = '';

		// Get element scripts
		$element_scripts = $element_instance->get_element_scripts();
		$element_type    = $element_instance->get_type();

		if ( ! isset( self::$loaded_javascript_assets[$element_type] ) ) {
			foreach ( $element_scripts as $script_url ) {
				$script_path = Utils::get_file_path_from_url( $script_url );
				$js         .= FileSystem::get_file_system()->get_contents( $script_path );
				self::$loaded_javascript_assets[$element_type] = true;
			}
		}

		// Check for children
		$childs = $element_instance->get_children();

		if ( is_array( $childs ) ) {
			foreach ( $childs as $element ) {
				$child_element_instance = Plugin::$instance->renderer->get_element_instance( $element['uid'] );
				if ( $child_element_instance ) {
					$js .= $this->get_javascript_for_element( $child_element_instance );
				}
			}
		}

		return $js;
	}

	/**
	 * Will compile dynamic css
	 *
	 * @return string The compiled css
	 */
	private function compile_dynamic_css() {
		$cache_directory    = $this->get_cache_directory();
		$dynamic_cache_file = $cache_directory['path'] . self::DYNAMIC_CSS_FILENAME;

		$dynamic_css = '';

		// Add css classes css
		$dynamic_css .= CSSClasses::get_css();

		$dynamic_css = apply_filters( 'zionbuilder/cache/dynamic_css', $dynamic_css );

		return FileSystem::get_file_system()->put_contents( $dynamic_cache_file, $this->minify( $dynamic_css ), 0644 );
	}

	/**
	 * Minify
	 *
	 * Will minify css code by removing comments and whitespaces
	 *
	 * @param string $css
	 *
	 * @return string The minified css
	 */
	public function minify( $css ) {
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove space after colons
		$css = str_replace( ': ', ':', $css );
		// Remove whitespace
		$css = str_replace( [ "\r\n", "\r", "\n", "\t" ], '', $css );

		return $css;
	}

	/**
	 * Delete Post Cache
	 *
	 * Deletes the cache file for a given post
	 *
	 * @param mixed $post_id
	 *
	 * @return void
	 */
	public function delete_post_cache( $post_id ) {
		$post_id               = absint( $post_id );
		$cache_file_config_css = $this->get_cache_file_config( $post_id );
		$cache_file_config_js  = $this->get_cache_file_config( $post_id, 'js' );

		FileSystem::get_file_system()->delete( $cache_file_config_css['path'] );
		FileSystem::get_file_system()->delete( $cache_file_config_js['path'] );
	}


	/**
	 * Purges the dynamic css cached file
	 *
	 * @return void
	 */
	public function delete_dynamic_css_cache() {
		$cache_directory    = $this->get_cache_directory();
		$dynamic_cache_file = $cache_directory['path'] . self::DYNAMIC_CSS_FILENAME;

		FileSystem::get_file_system()->delete( $dynamic_cache_file );
	}


	/**
	 * Delete all cache
	 *
	 * Deletes the cache directory
	 *
	 * @return boolean
	 */
	public function delete_all_cache() {
		$cache_directory = $this->get_cache_directory();
		return FileSystem::get_file_system()->delete( $cache_directory['path'], true );
	}

	/**
	 * Get Cache Version
	 *
	 * Returns a string based on post modified date that will be used as file version
	 *
	 * @param integer $post_id
	 *
	 * @return string
	 */
	private function get_cache_version( $post_id = 0 ) {
		$post_id = $post_id ? absint( $post_id ) : get_the_ID();

		return md5( get_post_modified_time( 'U', false, $post_id ) );
	}
}
