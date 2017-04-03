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
		// $html .= '<h2>' . __( 'Rokkaaa' , 'rokka-image-cdn' ) . '</h2>' . "\n";

		$html .= '<ul id="settings-sections" class="nav-tabs-wrap">' . "\n";
		$html .= '<li><a id="nav-tab-select-01" class="nav-tab current" href="#settings">' . __( 'Settings' , 'plugin_textdomain' ) . '</a></li>' . "\n";
		$html .= '<li><a id="nav-tab-select-02" class="nav-tab" href="#create">' . __( 'Create stacks on Rokka' , 'plugin_textdomain' ) . '</a></li>' . "\n";
		$html .= '<li><a id="nav-tab-select-03" class="nav-tab" href="#upload">' . __( 'Upload images to Rokka' , 'plugin_textdomain' ) . '</a></li>' . "\n";
		$html .= '</ul>' . "\n";
		$html .= '<div class="clear"></div>' . "\n";

		$html .= '<div id="column-left">' . "\n";
		$html .= '<div id="tab-01" class="current">' . "\n";
		$html .= '<div class="tab-content">' . "\n";
		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		// Get settings fields
		ob_start();
		settings_fields( $this->parent->_token . '_settings' );
		do_settings_sections( $this->parent->_token . '_settings' );
		$html .= ob_get_clean();

		$html .= '<table class="form-table">' . "\n";
		$html .= '<tbody>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= '<th scope="row"></th>' . "\n";
		$html .= '<td>' . "\n";
		$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'rokka-image-cdn' ) ) . '" />' . "\n";
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";

		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div><!--end #tab-01 -->' . "\n";


		$html .= '<div id="tab-02" class="display-none">' . "\n";
		$html .= '<div class="tab-content">' . "\n";

		$html .= '<table class="form-table">' . "\n";
		$html .= '<tbody>' . "\n";

		$html .= '<tr>' . "\n";
		// $html .= '<th scope="row"></th>' . "\n";
		$html .= '<td colspan="2">' . "\n";
		$html .=  esc_attr( __( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.' , 'rokka-image-cdn'));
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

		$html .= '<tr>' . "\n";
		$html .= '<td class="th">' . "\n";
		$html .= '<a href="#" class="button button-primary" id="create-rokka-stacks" >' . "\n";
		$html .=  esc_attr( __( 'Create stacks on Rokka' , 'rokka-image-cdn'));
		$html .= '</a>' . "\n";
		$html .= '</td>' . "\n";
		$html .= '<td>' . "\n";
		$html .= '<div id="progress_info_stacks"></div>' . "\n";
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";

		$html .= '</div>' . "\n";
		$html .= '</div><!--end #tab-02 -->' . "\n";


		$html .= '<div id="tab-03" class="display-none">' . "\n";
		$html .= '<div class="tab-content">' . "\n";
		$html .= '<a href="#" class="button button-primary" id="mass-upload-everything">' . "\n";
		$html .=  esc_attr( __( 'Upload images to Rokka' , 'rokka-image-cdn'));
		$html .= '</a>' . "\n";
		$html .= '<div id="progressbar"></div>' . "\n";
		$html .= '<div id="progress_info"></div>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div><!--end #tab-03 -->' . "\n";

		$html .= '</div><!--end #column-left -->' . "\n";



		$html .= '<div id="column-right">' . "\n";

		$html .= '<div class="column-right-wrap">' . "\n";
		$html .= '<div id="logo-rokka"><svg viewBox="0 0 206 48" xmlns="http://www.w3.org/2000/svg"><g id="Page-1-Copy-11" fill="#000" fill-rule="evenodd"><g id="Desktop-HD" transform="translate(-52 -53)"><g id="Group" transform="translate(52 53)"><path d="M65.652 38.602h7.122V28.72h2.013c.774 0 1.548 0 2.323-.155l4.955 10.037h7.741l-6.038-11.735c2.942-1.699 4.645-4.787 4.49-8.184 0-5.867-4.026-9.573-11.148-9.573H65.652v29.492zM76.8 15.286c2.942 0 4.18 1.236 4.18 3.706 0 2.625-1.548 3.86-4.49 3.86h-3.716v-7.566H76.8zm22.142 8.493c0-5.096 2.477-8.647 6.813-8.647 4.335 0 6.968 3.397 6.968 8.647 0 5.404-2.478 8.8-6.968 8.8-4.336 0-6.813-3.396-6.813-8.8zm-7.587 0c-.155 4.169 1.239 8.183 3.87 11.117 6.04 5.404 15.175 5.404 21.059 0 2.787-3.088 4.18-7.103 3.87-11.272.156-4.014-1.238-8.029-3.87-11.117-6.039-5.404-15.174-5.404-21.058 0-2.632 3.242-4.026 7.257-3.871 11.272zm39.948 14.823V26.558h2.942l6.813 12.044h8.207l-8.826-15.132 8.206-14.36h-7.897l-6.193 11.426h-3.097V9.11h-7.277v29.337h7.122v.155zm28.026 0V26.558h2.942l6.813 12.044h8.206l-8.825-15.132 8.051-14.206h-7.897l-6.193 11.427h-3.097V9.11h-7.123v29.337h7.123v.155zm18.116 0h7.742l1.548-5.405h9.29l1.704 5.405h7.742L195.097 9.264h-7.123l-10.529 29.338zm13.936-21.154l2.787 9.728h-5.574l2.787-9.728zm6.038-10.809V2.316h-11.767v4.17h11.767v.153z" id="rokka-text"></path><g id="rokka-box"><path id="Rechteck_6_Kopie_23" opacity=".8" d="M24.774 27.33L0 13.588v20.381l24.774 13.588z"></path><path id="Rechteck_6_Kopie_24" d="M24.774 27.33l24.774-13.588V33.97L24.774 47.557z"></path><path id="Rechteck_6_Kopie_23-2" opacity=".5" d="M49.548 13.588L24.774 0v20.382l24.774 13.587z"></path><path id="Rechteck_6_Kopie_23-3" opacity=".3" d="M0 13.588L24.774 0v20.382L0 33.969z"></path></g></g></g></g></svg></div>' . "\n";

		$html .= '<div id="banner-website" class="banner">' . "\n";
		$html .=  esc_attr( __( 'Banner Website' , 'rokka-image-cdn'));
		$html .= '</div>' . "\n";

		$html .= '<div id="banner-liip" class="banner">' . "\n";
		$html .= '<div>Banner 2<br>Limmatstrasse 183<br>CH-8005 Zürich<br>Switzerland</div>' . "\n";
		$html .= '</div>' . "\n";

		$html .= '</div>' . "\n";

		$html .= '</div><!--end #column-right -->' . "\n";



		$html .= '</div><!--end #wrap -->' . "\n";

		echo $html;
		?>

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
