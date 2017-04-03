<?php
/**
 * Rokka settings page
 *
 * @package rokka-wordpress-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Image_Cdn_Settings
 */
class Rokka_Image_Cdn_Settings {

	/**
	 * The single instance of Rokka_Image_Cdn_Settings.
	 *
	 * @var Rokka_Image_Cdn_Settings
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 *
	 * @var Rokka_Image_Cdn
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var string
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Instance of Rokka_Mass_Upload_Images.
	 *
	 * @var Rokka_Mass_Upload_Images
	 */
	private $rokka_mass_upload;

	/**
	 * Rokka_Image_Cdn_Settings constructor.
	 *
	 * @param Rokka_Image_Cdn          $parent The main plugin object.
	 * @param Rokka_Mass_Upload_Images $rokka_mass_upload Instance of Rokka_Mass_Upload_Images.
	 */
	public function __construct( $parent, $rokka_mass_upload ) {
		$this->rokka_mass_upload = $rokka_mass_upload;
		$this->parent = $parent;

		$this->base = 'rokka_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ), array(
			$this,
			'add_settings_link',
		) );
	}

	/**
	 * Initialize settings.
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_menu_item() {
		$page = add_options_page( __( 'Rokka Settings', 'rokka-image-cdn' ), __( 'Rokka Settings', 'rokka-image-cdn' ), 'manage_options', $this->parent->_token . '_settings', array(
			$this,
			'settings_page',
		) );
	}

	/**
	 * Add settings link to plugin list table.
	 *
	 * @param  array $links Existing links.
	 *
	 * @return array Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'rokka-image-cdn' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}

	/**
	 * Build settings fields.
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		$settings['standard'] = array(
			'title'       => __( 'Rokka ', 'rokka-image-cdn' ),
			'description' => __( 'Please enter your credentials below', 'rokka-image-cdn' ),
			'fields'      => array(
				array(
					'id'          => 'domain',
					'label'       => __( 'Rokka url', 'rokka-image-cdn' ),
					'description' => __( 'The domain where rokka images are stored. Don\'t change this value unless you know what you are doing', 'rokka-image-cdn' ),
					'type'        => 'url',
					'default'     => 'rokka.io',
					'disabled'    => 'disabled',
				),
				array(
					'id'          => 'company_name',
					'label'       => __( 'Company name', 'rokka-image-cdn' ),
					'description' => __( 'Your Company name you have registered on Rokka with', 'rokka-image-cdn' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Company' ),
				),
				array(
					'id'          => 'api_key',
					'label'       => __( 'API Key', 'rokka-image-cdn' ),
					'description' => __( 'Rokka API key', 'rokka-image-cdn' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Key' ),
				),
				array(
					'id'          => 'api_secret',
					'label'       => __( 'API Secret', 'rokka-image-cdn' ),
					'description' => __( 'This is a secret text field - any data saved here will not be displayed after the page has reloaded, but it will be saved.', 'rokka-image-cdn' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Secret' ),
				),
				array(
					'id'          => 'rokka_enabled',
					'label'       => __( 'Enable Rokka', 'rokka-image-cdn' ),
					'description' => __( 'This will enable the Rokka.io functionality.', 'rokka-image-cdn' ),
					'type'        => 'checkbox',
					'default'     => '',
				),
			),
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {
			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {
				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page
				//todo refactor this to make it more OOP
				if ( 'standard' === $section ) {
					add_settings_section( $section, $data['title'], array(
						$this,
						'settings_section',
					), $this->parent->_token . '_settings' );
				}

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field(
						$field['id'],
						$field['label'],
						array(
							$this->parent->admin,
							'display_field',
						),
						$this->parent->_token . '_settings',
						$section, array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Print settings section.
	 *
	 * @param array $section Settings section.
	 */
	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		// @codingStandardsIgnoreStart
		echo $html;
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Load settings page content.
	 */
	public function settings_page() {
		$rokka_settings = array(
			'imagesToUpload' => $this->rokka_mass_upload->get_images_for_upload(),
		);
		wp_localize_script( $this->parent->_token . '-settings-js', 'rokkaSettings', $rokka_settings );

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
		$html .= '<h2>' . __( 'Rokka Settings', 'rokka-image-cdn' ) . '</h2>' . "\n";

		$tab = '';
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= $_GET['tab'];
		}

		// Show page tabs
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) {
					if ( 0 == $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link
				$tab_link = add_query_arg(
					array(
						'tab' => $section,
					)
				);
				if ( isset( $_GET['settings-updated'] ) ) {
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++ $c;
			}

			$html .= '</h2>' . "\n";
		} // End if().

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		// Get settings fields
		ob_start();
		settings_fields( $this->parent->_token . '_settings' );
		do_settings_sections( $this->parent->_token . '_settings' );
		$html .= ob_get_clean();

		$html .= '<p class="submit">' . "\n";
		$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'rokka-image-cdn' ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";
		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
		?>
		<button class="button button-primary" id="create-rokka-stacks"><?php esc_attr_e( 'Create stacks on Rokka', 'rokka-image-cdn' ); ?></button>
		<div id="progress_info_stacks"></div>
		<br/>
		<button class="button button-primary" id="mass-upload-everything"><?php esc_attr_e( 'Upload images to Rokka', 'rokka-image-cdn' ); ?></button>
		<div id="progressbar"></div>
		<div id="progress_info"></div>

		<?php
	}

	/**
	 * Main Rokka_Image_Cdn_Settings Instance
	 *
	 * Ensures only one instance of Rokka_Image_Cdn_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return Rokka_Image_Cdn_Settings Rokka_Image_Cdn_Settings instance
	 */
	public static function instance( $parent, $mass_upload ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent, $mass_upload );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	}

}
