<?php
/**
 * Main class
 *
 * @package rokka-integration
 */

namespace Rokka_Integration;

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
	protected static $_instance = null;

	/**
	 * Rokka_Helper instance.
	 *
	 * @var Rokka_Helper
	 */
	public $rokka_helper = null;

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
	public $_version = '2.0.3';

	/**
	 * The plugin token.
	 *
	 * @var string
	 */
	public $_token = 'rokka-integration';

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
	 */
	public function __construct() {
		$this->define_constants();
		$this->init_plugin_environment();
		$this->includes();
		$this->init_hooks();
		$this->init_plugin();
	}

	/**
	 * Define plugin constants.
	 */
	protected function define_constants() {
		if ( ! defined( 'ROKKA_ABSPATH' ) ) {
			define( 'ROKKA_ABSPATH', trailingslashit( dirname( ROKKA_PLUGIN_FILE ) ) );
		}
	}

	/**
	 * Initializes plugin environment variables
	 */
	protected function init_plugin_environment() {
		// Load plugin environment variables
		$this->assets_dir = ROKKA_ABSPATH . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', ROKKA_PLUGIN_FILE ) ) );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		// Load plugin class files
		require_once ROKKA_ABSPATH . 'src/class-rokka-attachment.php';
		require_once ROKKA_ABSPATH . 'src/class-rokka-integration-settings.php';
		require_once ROKKA_ABSPATH . 'src/class-rokka-helper.php';
		require_once ROKKA_ABSPATH . 'src/class-rokka-media-management.php';
		require_once ROKKA_ABSPATH . 'src/class-rokka-rest.php';
		require_once ROKKA_ABSPATH . 'src/class-rokka-filter-url.php';
		require_once ROKKA_ABSPATH . 'src/class-rokka-filter-content.php';
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once ROKKA_ABSPATH . 'src/cli-command/class-rokka-wp-cli-command.php';
		}

		// add vendor library
		require_once ROKKA_ABSPATH . 'vendor/autoload.php';
	}

	/**
	 * Initializes hooks.
	 */
	protected function init_hooks() {
		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ), 10, 1 );

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// check version number on each request
		add_action( 'init', array( $this, 'check_version' ) );

		// display all collected notices if available
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Initialize plugin dependencies.
	 *
	 * @throws \Exception Throws exception if WP_CLI command couldn't be added.
	 */
	public function init_plugin() {
		$this->rokka_helper = new Rokka_Helper();

		if ( $this->rokka_helper->is_rokka_enabled() ) {
			new Rokka_Filter_Url( $this->rokka_helper );
			new Rokka_Attachment( $this->rokka_helper );
			new Rokka_Rest( $this->rokka_helper );
			if ( ! is_admin() && $this->rokka_helper->is_output_parsing_enabled() ) {
				new Rokka_Filter_Content( $this->rokka_helper );
			}
		}

		if ( is_admin() ) {
			if ( $this->rokka_helper->is_rokka_enabled() ) {
				new Rokka_Media_Management( $this->rokka_helper );
			}

			$this->settings = new Rokka_Integration_Settings( $this->rokka_helper, $this->_token, $this->assets_url );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command(
				'rokka',
				'\Rokka_Integration\CLI_Command\Rokka_WP_CLI_Command'
			);
		}
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
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( ROKKA_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Displays all admin notices
	 *
	 * @return bool
	 */
	public function show_admin_notices() {
		$notices = get_option( 'rokka_notices' );
		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return '';
		}

		// print all messages
		foreach ( $notices as $type => $messages ) {
			foreach ( $messages as $notice ) {
				echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
			}
		}

		return delete_option( 'rokka_notices' );
	}

	/**
	 * Main Rokka_Integration Instance
	 * Ensures only one instance of Rokka_Integration is loaded or can be loaded.
	 *
	 * @return Rokka_Integration Rokka_Integration instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
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
	 * Checks plugin version.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && get_option( $this->_token . '_version' ) !== $this->_version ) {
			$this->log_version_number();
			do_action( $this->_token . '_updated' );
		}
	}

	/**
	 * Log the plugin version number in database.
	 */
	protected function log_version_number() {
		delete_option( $this->_token . '_version' );
		update_option( $this->_token . '_version', $this->_version );
	}

}
