<?php
/**
 * Main class
 *
 * @package rokka-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Integration
 */
class Rokka_Integration {

	/**
	 * Rokka_Integration instance.
	 *
	 * @var Rokka_Integration
	 */
	private static $_instance = null;

	/**
	 * Rokka_Integration_Settings instance.
	 *
	 * @var Rokka_Integration_Settings
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
	 * Rokka_Integration constructor.
	 *
	 * @param string $file Main plugin file path.
	 * @param string $version Version number.
	 */
	public function __construct( $file = '', $version = '1.1.2' ) {
		$this->_version = $version;
		$this->_token   = 'rokka-integration';

		// Load plugin environment variables
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ), 10, 1 );

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load admin assets.
	 *
	 * @param string $hook Current page hook.
	 */
	public function admin_enqueue_assets( $hook ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );

		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin.min.js', array( 'jquery' ), $this->_version, true );
		wp_enqueue_script( $this->_token . '-admin' );

		$rokka_admin = array(
			'labels' => array(
				'deleteImageConfirm' => esc_html__( 'Do you really want to delete this image from rokka? Please be aware that all stored meta information (eg. subject area) will be deleted as well.', 'rokka-integration' ),
			),
		);
		wp_localize_script( $this->_token . '-admin', 'rokkaAdmin', $rokka_admin );

		wp_register_script( $this->_token . '-subject-area', esc_url( $this->assets_url ) . 'js/rokka-subject-area.min.js', array( 'jquery', 'imgareaselect' ), $this->_version, false );
		wp_enqueue_script( $this->_token . '-subject-area' );

		// Load only on rokka settings page
		if ( 'settings_page_' . $this->settings->menu_slug === $hook ) {
			wp_register_script( $this->_token . '-settings-js', $this->assets_url . 'js/settings.min.js', array( 'jquery' ), $this->_version, true );
			wp_enqueue_script( $this->_token . '-settings-js' );

			// add progessbar for mass upload
			wp_enqueue_script( 'jquery-ui-progressbar' );
			wp_register_style( $this->_token . '-jquery-ui', esc_url( $this->assets_url ) . '/css/jquery-ui.min.css', array(), '1.12.1' );
			wp_enqueue_style( $this->_token . '-jquery-ui' );
		}
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_plugin_textdomain() {
		$domain = 'rokka-integration'; // textdomain can't be stored in class variable since it must be a single string literal
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/languages/' );
	}

	/**
	 * Main Rokka_Integration Instance
	 * Ensures only one instance of Rokka_Integration is loaded or can be loaded.
	 *
	 * @param string $file Main plugin file path.
	 * @param string $version Plugin version.
	 *
	 * @return Rokka_Integration Rokka_Integration instance
	 */
	public static function instance( $file = '', $version = '1.1.2' ) {
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
