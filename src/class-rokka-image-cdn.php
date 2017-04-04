<?php
/**
 * Main class
 *
 * @package rokka-wordpress-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Image_Cdn
 */
class Rokka_Image_Cdn {

	/**
	 * Rokka_Image_Cdn instance.
	 *
	 * @var Rokka_Image_Cdn
	 */
	private static $_instance = null;

	/**
	 * Rokka_Image_Cdn_Settings instance.
	 *
	 * @var Rokka_Image_Cdn_Settings
	 */
	public $settings = null;

	/**
	 * The plugin version number.
	 *
	 * @var string
	 */
	public $_version;

	/**
	 * The token.
	 *
	 * @var string
	 */
	public $_token;

	/**
	 * The main plugin file.
	 *
	 * @var string
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var string
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var string
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var string
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 *
	 * @var string
	 */
	public $script_suffix;

	/**
	 * Rokka_Image_Cdn constructor.
	 *
	 * @param string $file Main plugin file path.
	 * @param string $version Version number.
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token   = 'rokka-image-cdn';

		// Load plugin environment variables
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	}

	/**
	 * Load admin CSS.
	 */
	public function admin_enqueue_styles() {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	}

	/**
	 * Load admin Javascript.
	 *
	 * @param string $hook Current page hook.
	 */
	public function admin_enqueue_scripts( $hook ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin.js', array( 'jquery' ), $this->_version, false );
		wp_enqueue_script( $this->_token . '-admin' );
		wp_register_script( $this->_token . '-subject-area', esc_url( $this->assets_url ) . 'js/rokka-subject-area.js', array( 'jquery', 'imgareaselect' ), $this->_version, false );
		wp_enqueue_script( $this->_token . '-subject-area' );

		// Load only on rokka settings page
		if ( 'settings_page_rokka-image-cdn_settings' === $hook ) {
			wp_register_script( $this->_token . '-settings-js', $this->assets_url . 'js/settings' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
			wp_enqueue_script( $this->_token . '-settings-js' );

			// add progessbar for mass upload
			wp_enqueue_script( 'jquery-ui-progressbar' );
			wp_enqueue_style( 'rokka-jquery-ui', ROKKA_PLUGIN_PATH . '/assets/css/jquery-ui.min.css' );
			wp_enqueue_style( 'rokka-jquery-ui' );
		}
	}

	/**
	 * Load plugin localisation.
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'rokka-image-cdn', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_plugin_textdomain() {
		$domain = 'rokka-image-cdn'; // textdomain can't be stored in class variable since it must be a single string literal
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Main rokka-image-cdn Instance
	 * Ensures only one instance of rokka-image-cdn is loaded or can be loaded.
	 *
	 * @param string $file Main plugin file path.
	 * @param string $version Plugin version.
	 *
	 * @return Rokka_Image_Cdn rokka-image-cdn instance
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?' ), esc_attr( $this->_version ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?' ), esc_attr( $this->_version ) );
	}

	/**
	 * Installation. Runs on activation.
	 */
	public function install() {
		$this->_log_version_number();
	}

	/**
	 * Log the plugin version number in database.
	 */
	private function _log_version_number() {
		update_option( $this->_token . '_version', $this->_version );
	}

}
