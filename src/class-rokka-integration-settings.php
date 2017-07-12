<?php
/**
 * Rokka settings page
 *
 * @package rokka-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Integration_Settings
 */
class Rokka_Integration_Settings {

	/**
	 * The single instance of Rokka_Integration_Settings.
	 *
	 * @var Rokka_Integration_Settings
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 *
	 * @var Rokka_Integration
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var string
	 */
	public $base = '';

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	public $menu_slug = '';

	/**
	 * Available settings fields for plugin.
	 *
	 * @var array
	 */
	public $settings_fields = array();

	/**
	 * Instance of Rokka_Helper.
	 *
	 * @var Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Rokka_Integration_Settings constructor.
	 *
	 * @param Rokka_Integration $parent The main plugin object.
	 * @param Rokka_Helper      $rokka_helper Instance of Rokka_Helper.
	 */
	public function __construct( $parent, $rokka_helper ) {
		$this->rokka_helper = $rokka_helper;
		$this->parent = $parent;

		$this->base = 'rokka_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Register plugin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings link to plugin list table
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ), array(
			$this,
			'add_settings_link',
		) );

		// Add endpoints for AJAX actions
		add_action( 'wp_ajax_rokka_upload_image', array( $this, 'ajax_rokka_upload_image' ) );
		add_action( 'wp_ajax_rokka_delete_image', array( $this, 'ajax_rokka_delete_image' ) );
		add_action( 'wp_ajax_rokka_sync_stacks', array( $this, 'ajax_rokka_sync_stacks' ) );
		add_action( 'wp_ajax_rokka_check_credentials', array( $this, 'ajax_rokka_check_credentials' ) );
	}

	/**
	 * Initialize settings.
	 */
	public function init_settings() {
		$this->settings_fields = array(
			array(
				'id'          => 'company_name',
				'label'       => __( 'Company name', 'rokka-integration' ),
				'type'        => 'text',
				'placeholder' => __( 'my-company' ),
				'constant_name' => Rokka_Helper::OPTION_COMPANY_NAME_CONSTANT_NAME,
			),
			array(
				'id'          => 'api_key',
				'label'       => __( 'API Key', 'rokka-integration' ),
				'type'        => 'text',
				'placeholder' => __( 'My API Key' ),
				'constant_name' => Rokka_Helper::OPTION_API_KEY_CONSTANT_NAME,
			),
			array(
				'id'          => 'stack_prefix',
				'label'       => __( 'Stack Prefix', 'rokka-integration' ),
				/* translators: %s contains default stack prefix */
				'description' => sprintf( _x( "You can use this prefix to create unique stacknames on rokka. So that your already existing stacks won't be overwritten. Since the stack name is used in the URL only a-z (lower case a-z), 0-9, - (dashes) and _ (underscores) are allowed. Default %s", '%s contains default stack prefix', 'rokka-integration' ), Rokka_Helper::STACK_PREFIX_DEFAULT ),
				'type'        => 'text',
				'placeholder' => Rokka_Helper::STACK_PREFIX_DEFAULT,
				'sanitize_callback' => array( $this, 'sanitize_stack_prefix' ),
				'constant_name' => Rokka_Helper::OPTION_STACK_PREFIX_CONSTANT_NAME,
			),
			array(
				'id'          => 'rokka_enabled',
				'label'       => __( 'Enable rokka integration', 'rokka-integration' ),
				'description' => __( 'This will enable the rokka integration. Please make sure that you already have synced the stacks to rokka before enabling this.', 'rokka-integration' ),
				'type'        => 'checkbox',
			),
			array(
				'id'          => 'autoformat',
				'label'       => __( 'Enable WebP format', 'rokka-integration' ),
				'description' => __( 'If you enable this option, rokka will deliver an image in the usually smaller WebP format instead of PNG or JPG, if the client supports it', 'rokka-integration' ),
				'type'        => 'checkbox',
			),
			array(
				'id'          => 'delete_previous',
				'label'       => __( 'Delete previous images if metadata changes', 'rokka-integration' ),
				'description' => __( "Enable this if you don't need to keep the previous image on rokka if you change something on the metadata of an image (eg. subject area).", 'rokka-integration' ),
				'type'        => 'checkbox',
			),
			array(
				'id'          => 'output_parsing',
				'label'       => __( 'Enable output parsing', 'rokka-integration' ),
				'description' => __( 'This feature will parse the output and replaces urls to local images with rokka image urls. Relative links will not be replaced.', 'rokka-integration' ),
				'type'        => 'checkbox',
			),
		);
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_menu_item() {
		$this->menu_slug = $this->parent->_token . '_settings';
		add_options_page( __( 'Rokka Settings', 'rokka-integration' ), __( 'Rokka Settings', 'rokka-integration' ), 'manage_options', $this->menu_slug, array( $this, 'settings_page' ) );
	}

	/**
	 * Add settings link to plugin list table.
	 *
	 * @param  array $links Existing links.
	 *
	 * @return array Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . $this->parent->_token . '_settings' ) ) . '">' . esc_html__( 'Settings', 'rokka-integration' ) . '</a>';
		// add settings link as first element
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		$section = 'default';

		// Add section to page
		add_settings_section( $section, __( 'Main settings', 'rokka-integration' ), array(
			$this,
			'settings_section',
		), $this->parent->_token . '_settings' );

		foreach ( $this->settings_fields as $field ) {
			// Register field
			$option_name = $this->base . $field['id'];
			if ( array_key_exists( 'sanitize_callback', $field ) ) {
				register_setting( $this->parent->_token . '_settings', $option_name, $field['sanitize_callback'] );
			} else {
				register_setting( $this->parent->_token . '_settings', $option_name );
			}

			// Add field to page
			add_settings_field(
				$field['id'],
				$field['label'],
				array(
					$this,
					'display_field',
				),
				$this->parent->_token . '_settings',
				$section,
				array(
					'field' => $field,
					'prefix' => $this->base,
					'label_for' => $field['id'],
					'constant_name' => array_key_exists( 'constant_name', $field ) ? $field['constant_name'] : '',
				)
			);

			// disable saving of options which are stored in constants
			if ( array_key_exists( 'constant_name', $field ) && ! empty( $field['constant_name'] ) && defined( $field['constant_name'] ) ) {
				global $new_whitelist_options;
				$option_key = array_search( $option_name, $new_whitelist_options[ $this->parent->_token . '_settings' ], true );
				if ( false !== $option_key ) {
					unset( $new_whitelist_options[ $this->parent->_token . '_settings' ][ $option_key ] );
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
	}

	/**
	 * Load settings page content.
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rokka-integration' ) );
		}

		// Show warning if rokka is not enabled
		if ( ! $this->rokka_helper->are_settings_complete() ) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Settings need to be filled out completely before rokka support can be enabled.', 'rokka-integration' ) . '</p></div>';
		} elseif ( $this->rokka_helper->are_settings_complete() && ! $this->rokka_helper->is_rokka_enabled() ) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Rokka integration is not enabled yet. Please select the \'Enable rokka\' checkbox in the settings.', 'rokka-integration' ) . '</p></div>';
		}

		$current_tab = 'settings';
		if ( isset( $_GET['tab'] ) ) {
			check_admin_referer( 'rokka-settings-tab' );
			$current_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
		}

		$ajax_nonce = wp_create_nonce( 'rokka-settings' );
		$images_to_upload = $this->get_images_to_upload();
		$images_to_delete = $this->get_images_to_delete();
		$rokka_settings = array(
			'imagesToUpload' => $images_to_upload,
			'imagesToDelete' => $images_to_delete,
			'nonce' => $ajax_nonce,
			'loadingSpinnerUrl' => esc_url( admin_url( 'images/spinner-2x.gif' ) ),
			'labels' => array(
				'createStacksStart' => esc_html__( 'Creating stacks...', 'rokka-integration' ),
				'syncStacksSuccess' => esc_html__( 'Stack sync successful! Please reload this page to update status.', 'rokka-integration' ),
				'syncStacksFail' => esc_html__( 'Stack sync failed! Error:', 'rokka-integration' ),
				'uploadSingleImageSuccess' => esc_html__( 'Upload of image successful. Image ID:', 'rokka-integration' ),
				'uploadSingleImageFail' => esc_html__( 'Upload of image failed! Image ID:', 'rokka-integration' ),
				'uploadImagesSuccess' => esc_html__( 'Image upload finished!', 'rokka-integration' ),
				'uploadImagesFail' => esc_html__( 'There was an error during the upload of the images!', 'rokka-integration' ),
				'uploadImagesAlreadyUploaded' => esc_html__( 'Nothing to process here, all images are already uploaded to rokka.', 'rokka-integration' ),
				'deleteSingleImageSuccess' => esc_html__( 'Image successfully removed. Image ID:', 'rokka-integration' ),
				'deleteSingleImageFail' => esc_html__( 'Removing of image failed! Image ID:', 'rokka-integration' ),
				'deleteImagesConfirm' => esc_html__( 'Do you really want to delete all images from rokka?', 'rokka-integration' ),
				'deleteImagesSuccess' => esc_html__( 'All images have been removed!', 'rokka-integration' ),
				'deleteImagesFail' => esc_html__( 'There was an error during the removal of the images!', 'rokka-integration' ),
				'deleteImagesNoImage' => esc_html__( 'Nothing to process here, there are no images on rokka yet.', 'rokka-integration' ),
			),
		);
		wp_localize_script( $this->parent->_token . '-settings-js', 'rokkaSettings', $rokka_settings );
		?>
		<div class="wrap" id="<?php echo esc_attr( $this->parent->_token ); ?>_settings">
			<h1><?php esc_html_e( 'Rokka Settings' , 'rokka-integration' ); ?></h1>

			<div id="column-left">
				<div id="settings-sections" class="nav-tabs-wrap">
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=' . $this->parent->_token . '_settings&tab=settings' ), 'rokka-settings-tab' ) ); ?>" class="nav-tab<?php echo 'settings' === $current_tab ? ' active' : ''; ?>"><?php esc_html_e( 'Settings' , 'rokka-integration' ); ?></a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=' . $this->parent->_token . '_settings&tab=stacks' ), 'rokka-settings-tab' ) ); ?>" class="nav-tab<?php echo 'stacks' === $current_tab ? ' active' : ''; ?>"><?php esc_html_e( 'Sync stacks' , 'rokka-integration' ); ?></a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=' . $this->parent->_token . '_settings&tab=upload' ), 'rokka-settings-tab' ) ); ?>" class="nav-tab<?php echo 'upload' === $current_tab ? ' active' : ''; ?>"><?php esc_html_e( 'Mass upload/delete' , 'rokka-integration' ); ?></a>
				</div>
				<?php if ( 'stacks' === $current_tab ) : ?>
					<div class="tab-content">
						<?php if ( $this->rokka_helper->are_settings_complete() ) : ?>
							<h2><?php esc_html_e( 'Sync stacks' , 'rokka-integration' ); ?></h2>
							<p>
								<?php esc_html_e( 'Stacks are a set of operations on rokka which represent the image sizes as they are defined in Wordpress. If you change the image sizes in Wordpress, execute this command again in order to reflect pass the size changes to the stacks on rokka.' , 'rokka-integration' ); ?>
							</p>
							<?php
							try {
								$stacks_to_sync = $this->rokka_helper->get_stacks_to_sync();
								?>

								<?php if ( ! empty( $stacks_to_sync ) ) : ?>
									<table class="stack-sync">
										<thead>
										<tr>
											<th class="name"><?php esc_html_e( 'Stack name', 'rokka-integration' ); ?></th>
											<th class="width"><?php esc_html_e( 'Width', 'rokka-integration' ); ?></th>
											<th class="height"><?php esc_html_e( 'Height', 'rokka-integration' ); ?></th>
											<th class="crop"><?php esc_html_e( 'Crop', 'rokka-integration' ); ?></th>
											<th class="status"><?php esc_html_e( 'Sync status', 'rokka-integration' ); ?></th>
										</tr>
										</thead>
										<tbody>
										<?php foreach ( $stacks_to_sync as $stack ) : ?>
											<?php
											$stack_operation_name = __( 'All good!', 'rokka-integration' );
											switch ( $stack['operation'] ) {
												case Rokka_Helper::STACK_SYNC_OPERATION_CREATE:
													$stack_operation_name = __( 'Stack will be created', 'rokka-integration' );
													break;
												case Rokka_Helper::STACK_SYNC_OPERATION_UPDATE:
													$stack_operation_name = __( 'Stack will be updated', 'rokka-integration' );
													break;
												case Rokka_Helper::STACK_SYNC_OPERATION_DELETE:
													$stack_operation_name = __( 'Stack will be deleted', 'rokka-integration' );
													break;
											}
											?>
											<tr class="<?php echo esc_attr( $stack['operation'] ); ?>">
												<?php if (
													$this->rokka_helper->get_stack_prefix() . $this->rokka_helper->get_rokka_full_size_stack_name() === $stack['name'] ||
													Rokka_Helper::STACK_SYNC_OPERATION_DELETE === $stack['operation']
												) : ?>
													<td><?php echo esc_html( $stack['name'] ); ?></td>
													<td>-</td>
													<td>-</td>
													<td>-</td>
													<td><?php echo esc_html( $stack_operation_name ); ?></td>
												<?php else : ?>
													<td><?php echo esc_html( $stack['name'] ); ?></td>
													<td><?php echo esc_html( $stack['width'] ); ?></td>
													<td><?php echo esc_html( $stack['height'] ); ?></td>
													<td><?php $stack['crop'] ? esc_html_e( 'Yes', 'rokka-integration' ) : esc_html_e( 'No', 'rokka-integration' ); ?></td>
													<td><?php echo esc_html( $stack_operation_name ); ?></td>
												<?php endif ; ?>
											</tr>
										<?php endforeach ; ?>
										</tbody>
									</table>
									<button class="button button-primary" id="sync-rokka-stacks" ><?php esc_html_e( 'Sync stacks with rokka' , 'rokka-integration' ); ?></button>
									<div id="progress-info-stacks"></div>
								<?php else : ?>
									<p><?php esc_html_e( 'There are no image sizes defined in WordPress.', 'rokka-integration' ); ?></p>
								<?php endif ; ?>
							<?php } catch ( Exception $e ) { ?>
								<p>
									<?php
									printf(
										// translators: %s contains the error from rokka
										esc_html_x(
											'There was an error listing the stacks from rokka. %s',
											'%s contains the error from rokka',
											'rokka-integration'
										),
										esc_html( $e->getMessage() )
									);
									?>
								</p>
							<?php } ?>

						<?php else : ?>
							<p><?php esc_html_e( 'Please enable rokka first (in main settings).', 'rokka-integration' ); ?></p>
						<?php endif ; ?>
					</div>
				<?php elseif ( 'upload' === $current_tab ) : ?>
					<div class="tab-content">
						<?php if ( $this->rokka_helper->is_rokka_enabled() ) : ?>
							<h2><?php esc_html_e( 'Mass upload images to rokka' , 'rokka-integration' ); ?></h2>
							<?php if ( ! empty( $images_to_upload ) ) : ?>
								<?php
								echo '<p>' . esc_html__( 'The following images will be uploaded to rokka:' , 'rokka-integration' ) . '</p>';
								echo '<ul class="image-list">';
								foreach ( $images_to_upload as $image_id ) {
									$image_name = get_attached_file( $image_id );
									/* translators: %1$s contains image id. %2$s contains image path. */
									echo '<li>' . sprintf( esc_html_x( 'ID: %1$s / Path: %2$s', '%1$s contains image id. %2$s contains image path.', 'rokka-integration' ), esc_html( $image_id ), esc_html( $image_name ) ) . '</li>';
								}
								echo '</ul>'
								?>
								<button class="button button-primary" id="mass-upload-everything"><?php esc_attr_e( 'Upload all images to rokka' , 'rokka-integration' ); ?></button>
								<div id="upload-progress-info"></div>
								<div id="upload-progressbar"></div>
								<div id="upload-progress-log-wrapper">
									<label for="upload-progress-log"><?php esc_html_e( 'Log:', 'rokka-integration' ); ?></label>
									<textarea id="upload-progress-log" disabled="disabled"></textarea>
								</div>
							<?php else : ?>
								<p>
									<?php esc_html_e( 'All images are already uploaded to rokka. Nothing to do here.' , 'rokka-integration' ); ?>
								</p>
							<?php endif ; ?>

							<h2><?php esc_html_e( 'Danger zone - Mass delete images' , 'rokka-integration' ); ?></h2>
							<?php if ( ! empty( $images_to_delete ) ) : ?>
								<?php
								echo '<p>' . esc_html__( 'The following images will be deleted from rokka:' , 'rokka-integration' ) . '</p>';
								echo '<ul class="image-list">';
								foreach ( $images_to_delete as $image_id ) {
									$image_name = get_attached_file( $image_id );
									/* translators: %1$s contains image id. %2$s contains image path. */
									echo '<li>' . sprintf( esc_html_x( 'ID: %1$s / Path: %2$s', '%1$s contains image id. %2$s contains image path.', 'rokka-integration' ), esc_html( $image_id ), esc_html( $image_name ) ) . '</li>';
								}
								echo '</ul>';
								?>
								<button class="button delete" id="mass-delete-everything"><?php esc_attr_e( 'Remove all images from rokka' , 'rokka-integration' ); ?></button>
								<div id="delete-progress-info"></div>
								<div id="delete-progressbar"></div>
								<div id="delete-progress-log-wrapper">
									<label for="delete-progress-log"><?php esc_html_e( 'Log:', 'rokka-integration' ); ?></label>
									<textarea id="delete-progress-log" disabled="disabled"></textarea>
								</div>
							<?php else : ?>
								<p>
									<?php esc_html_e( 'There are no images on rokka yet. Please upload them first.' , 'rokka-integration' ); ?>
								</p>
							<?php endif ; ?>
						<?php else : ?>
							<p><?php esc_html_e( 'Please enable rokka first (in main settings)', 'rokka-integration' ); ?></p>
						<?php endif ; ?>
					</div>
				<?php else : ?>
					<div class="tab-content">
						<form method="post" action="options.php" enctype="multipart/form-data">
							<?php
							// Get settings fields
							settings_fields( $this->parent->_token . '_settings' );
							do_settings_sections( $this->parent->_token . '_settings' );
							submit_button();
							?>
							<?php if ( $this->rokka_helper->are_settings_complete() ) : ?>
								<button class="button button-secondary" id="check-rokka-credentials"><?php esc_attr_e( 'Check rokka crendentials' , 'rokka-integration' ); ?></button>
								<div id="rokka-credentials-status"></div>
							<?php endif ; ?>
						</form>
					</div>
				<?php endif ; ?>
			</div><!--end #column-left -->

			<div id="column-right">
				<div class="column-right-wrap">
					<div id="logo-rokka" class="logo">
						<a href="https://rokka.io">
							<svg viewBox="0 0 206 48" xmlns="http://www.w3.org/2000/svg"><g id="Page-1-Copy-11" fill="#000" fill-rule="evenodd"><g id="Desktop-HD" transform="translate(-52 -53)"><g id="Group" transform="translate(52 53)"><path d="M65.652 38.602h7.122V28.72h2.013c.774 0 1.548 0 2.323-.155l4.955 10.037h7.741l-6.038-11.735c2.942-1.699 4.645-4.787 4.49-8.184 0-5.867-4.026-9.573-11.148-9.573H65.652v29.492zM76.8 15.286c2.942 0 4.18 1.236 4.18 3.706 0 2.625-1.548 3.86-4.49 3.86h-3.716v-7.566H76.8zm22.142 8.493c0-5.096 2.477-8.647 6.813-8.647 4.335 0 6.968 3.397 6.968 8.647 0 5.404-2.478 8.8-6.968 8.8-4.336 0-6.813-3.396-6.813-8.8zm-7.587 0c-.155 4.169 1.239 8.183 3.87 11.117 6.04 5.404 15.175 5.404 21.059 0 2.787-3.088 4.18-7.103 3.87-11.272.156-4.014-1.238-8.029-3.87-11.117-6.039-5.404-15.174-5.404-21.058 0-2.632 3.242-4.026 7.257-3.871 11.272zm39.948 14.823V26.558h2.942l6.813 12.044h8.207l-8.826-15.132 8.206-14.36h-7.897l-6.193 11.426h-3.097V9.11h-7.277v29.337h7.122v.155zm28.026 0V26.558h2.942l6.813 12.044h8.206l-8.825-15.132 8.051-14.206h-7.897l-6.193 11.427h-3.097V9.11h-7.123v29.337h7.123v.155zm18.116 0h7.742l1.548-5.405h9.29l1.704 5.405h7.742L195.097 9.264h-7.123l-10.529 29.338zm13.936-21.154l2.787 9.728h-5.574l2.787-9.728zm6.038-10.809V2.316h-11.767v4.17h11.767v.153z" id="rokka-text"></path><g id="rokka-box"><path id="Rechteck_6_Kopie_23" opacity=".8" d="M24.774 27.33L0 13.588v20.381l24.774 13.588z"></path><path id="Rechteck_6_Kopie_24" d="M24.774 27.33l24.774-13.588V33.97L24.774 47.557z"></path><path id="Rechteck_6_Kopie_23-2" opacity=".5" d="M49.548 13.588L24.774 0v20.382l24.774 13.587z"></path><path id="Rechteck_6_Kopie_23-3" opacity=".3" d="M0 13.588L24.774 0v20.382L0 33.969z"></path></g></g></g></g></svg>
						</a>
					</div>
					<div id="logo-liip" class="logo">
						<a href="https://liip.ch">
							<img src="<?php echo esc_url( $this->parent->assets_url . '/images/logo-liip.png' ); ?>" alt="<?php esc_html_e( 'Liip Logo', 'rokka-integration' ); ?>" />
						</a>
					</div>
					<div id="address-block">
						<span class="company">Liip AG</span><br />
						Limmatstrasse 183<br />
						CH-8005 Zürich
					</div>
				</div>
			</div><!--end #column-right -->
		</div><!--end #wrap -->
		<?php
	}

	/**
	 * Generate HTML for displaying fields
	 *
	 * @param array $data Additional data which is added in add_settings_field() method.
	 */
	public function display_field( $data = array() ) {
		// Get field info
		if ( isset( $data['field'] ) ) {
			$field = $data['field'];
		} else {
			$field = $data;
		}

		// Check for prefix on option name
		$option_name = '';
		if ( isset( $data['prefix'] ) ) {
			$option_name = $data['prefix'];
		}

		// Get saved data
		$option_value = '';

		$option_name .= $field['id'];
		if ( ! empty( $data['constant_name'] ) && defined( $data['constant_name'] ) ) {
			$option = constant( $data['constant_name'] );
		} else {
			$option = get_option( $option_name );
		}

		// Get data to display in field
		if ( isset( $option ) ) {
			$option_value = $option;
		}

		// Show default data if no option saved and default is supplied
		if ( false === $option_value && isset( $field['default'] ) ) {
			$option_value = $field['default'];
		} elseif ( false === $option_value ) {
			$option_value = '';
		}

		$html = '';

		switch ( $field['type'] ) {
			case 'text':
			case 'url':
			case 'email':
				$placeholder = ( array_key_exists( 'placeholder', $field ) ? $field['placeholder'] : '' );
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" class="' . ( ! empty( $data['constant_name'] ) &&  defined( $data['constant_name'] ) ? 'disabled' : '' ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $option_value ) . '" ' . disabled( ! empty( $data['constant_name'] ) && defined( $data['constant_name'] ), true, false ) . '/>' . "\n";
				break;

			case 'textarea':
				$placeholder = ( array_key_exists( 'placeholder', $field ) ? $field['placeholder'] : '' );
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . $option_value . '</textarea><br/>' . "\n";
				break;

			case 'checkbox':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" value="1" ' . checked( '1', $option_value, false ) . '/>' . "\n";
				break;

			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $k, $option_value, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( $k === $option_value ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select>';
				break;
		}

		if ( array_key_exists( 'description', $field ) ) {
			switch ( $field['type'] ) {
				case 'radio':
					$html .= '<br/><span class="description">' . $field['description'] . '</span>';
					break;

				case 'checkbox':
					$html .= '<span class="description">' . $field['description'] . '</span>';
					break;

				default:
					$html .= '<div><span class="description">' . $field['description'] . '</span></div>' . "\n";
					break;
			}
		}

		// @codingStandardsIgnoreStart
		echo $html;
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Sanitizes stack prefix before saving it to database
	 *
	 * @param string $value Value to sanitize.
	 * @return string
	 */
	public function sanitize_stack_prefix( $value ) {
		$value = sanitize_title( $value );

		if ( empty( $value ) ) {
			return $value;
		}

		// since sanitize_title removes all dashes around the string we add one dash after the prefix
		$value .= '-';
		return $value;
	}

	/**
	 * Get all images which are not yet uploaded to rokka.
	 *
	 * @return array Array with ids of images.
	 */
	public function get_images_to_upload() {
		$image_ids = $this->get_all_images();

		$image_ids = array_filter( $image_ids, function ( $image_id ) {
			return ! $this->rokka_helper->is_on_rokka( $image_id );
		} );
		// reset keys to get a proper array to send to javascript (not an associative array)
		$image_ids = array_values( $image_ids );

		return $image_ids;
	}

	/**
	 * Get all images which are already uploaded to rokka.
	 *
	 * @return array Array with ids of images.
	 */
	public function get_images_to_delete() {
		$image_ids = $this->get_all_images();

		$image_ids = array_filter( $image_ids, function ( $image_id ) {
			return $this->rokka_helper->is_on_rokka( $image_id );
		} );
		// reset keys to get a proper array to send to javascript (not an associative array)
		$image_ids = array_values( $image_ids );

		return $image_ids;
	}

	/**
	 * Get all images from database.
	 *
	 * @return array Array with ids of images.
	 */
	private function get_all_images() {
		$query_images_args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => Rokka_Helper::ALLOWED_MIME_TYPES,
			'post_status'    => 'inherit',
			// @codingStandardsIgnoreStart
			'posts_per_page' => -1,
			// @codingStandardsIgnoreEnd
			'fields'         => 'ids',
		);

		return get_posts( $query_images_args );
	}

	/**
	 * Upload image to rokka (rokka_upload_image ajax endpoint)
	 */
	public function ajax_rokka_upload_image() {
		$nonce_valid = check_ajax_referer( 'rokka-settings', 'nonce', false );

		if ( ! $nonce_valid ) {
			wp_send_json_error( __( 'Permission denied! There was something wrong with the nonce.', 'rokka-integration' ), 403 );
			wp_die();
		}

		try {
			if ( isset( $_POST['image_id'] ) ) {
				$image_id = intval( $_POST['image_id'] );

				if ( ! $this->rokka_helper->is_on_rokka( $image_id ) ) {
					$upload_success = $this->rokka_helper->upload_image_to_rokka( $image_id );

					if ( $upload_success ) {
						wp_send_json_success( $image_id );
					} else {
						wp_send_json_error( $image_id, 400 );
					}
				} else {
					wp_send_json_error( __( 'This image is already on rokka. No need to upload it another time.', 'rokka-integration' ), 400 );
				}
			} else {
				wp_send_json_error( __( 'image_id parameter missing.', 'rokka-integration' ), 400 );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 400 );
		}

		wp_die();
	}

	/**
	 * Deletes image from rokka (rokka_delete_image ajax endpoint)
	 */
	public function ajax_rokka_delete_image() {
		$nonce_valid = check_ajax_referer( 'rokka-settings', 'nonce', false );

		if ( ! $nonce_valid ) {
			wp_send_json_error( __( 'Permission denied! There was something wrong with the nonce.', 'rokka-integration' ), 403 );
			wp_die();
		}

		try {
			if ( isset( $_POST['image_id'] ) ) {
				$image_id = intval( $_POST['image_id'] );

				if ( $this->rokka_helper->is_on_rokka( $image_id ) ) {
					$delete_success = $this->rokka_helper->delete_image_from_rokka( $image_id );

					if ( $delete_success ) {
						wp_send_json_success( $image_id );
					} else {
						wp_send_json_error( $image_id, 400 );
					}
				} else {
					wp_send_json_error( __( 'This image is not yet on rokka. No need to delete it.', 'rokka-integration' ), 400 );
				}
			} else {
				wp_send_json_error( __( 'image_id parameter missing.', 'rokka-integration' ), 400 );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 400 );
		}

		wp_die();
	}

	/**
	 * Sync stacks to rokka (rokka_sync_stacks ajax endpoint)
	 */
	public function ajax_rokka_sync_stacks() {
		$nonce_valid = check_ajax_referer( 'rokka-settings', 'nonce', false );

		if ( ! $nonce_valid ) {
			wp_send_json_error( __( 'Permission denied! There was something wrong with the nonce.', 'rokka-integration' ), 403 );
			wp_die();
		}

		try {
			$synced_stacks = $this->rokka_helper->rokka_sync_stacks();

			if ( ! empty( $synced_stacks ) ) {
				wp_send_json_success( $synced_stacks );
				wp_die();
			}

			wp_send_json_error( __( 'Could not process stacks.', 'rokka-integration' ), 400 );
			wp_die();
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 400 );
		}

	}

	/**
	 * Checks rokka credentials (rokka_check_credentials ajax endpoint)
	 */
	public function ajax_rokka_check_credentials() {
		$nonce_valid = check_ajax_referer( 'rokka-settings', 'nonce', false );

		if ( ! $nonce_valid ) {
			wp_send_json_error( __( 'Permission denied! There was something wrong with the nonce.', 'rokka-integration' ), 403 );
			wp_die();
		}

		if ( $this->rokka_helper->check_rokka_credentials() ) {
			wp_send_json_success( __( 'Yay! Your rokka credentials are valid.', 'rokka-integration' ) );
			wp_die();
		} else {
			wp_send_json_error( __( 'Whops! Something is wrong with your rokka credentials.', 'rokka-integration' ), 400 );
			wp_die();
		}
	}

	/**
	 * Main Rokka_Integration_Settings Instance
	 *
	 * Ensures only one instance of Rokka_Integration_Settings is loaded or can be loaded.
	 *
	 * @param Rokka_Integration $parent The main plugin object.
	 * @param Rokka_Helper      $rokka_helper Instance of Rokka_Helper.
	 *
	 * @static
	 * @return Rokka_Integration_Settings Rokka_Integration_Settings instance
	 */
	public static function instance( $parent, $rokka_helper ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent, $rokka_helper );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?' ), esc_attr( $this->parent->_version ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?' ), esc_attr( $this->parent->_version ) );
	}

}
