<?php
/**
 * Rokka helper class.
 *
 * @package rokka-cli-command
 */

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Class Rokka_Wp_Cli_Command
 */
class Rokka_Wp_Cli_Command extends WP_CLI_Command {

	/**
	 * Create Stacks at Rokka via API.
	 *
	 * ## EXAMPLE
	 * 		wp rokka create_stack --name=banner-large --width=1600 --height=700 --crop
	 *
	 * @param array $args Arguments from cli-command.
	 * @param array $assoc_args Associative arguments from cli-command.
	 */
	public function create_stack( $args, $assoc_args ) {
		if ( isset( $assoc_args['name'] ) && isset( $assoc_args['width'] ) && isset( $assoc_args['height'] ) &&
			'' !== $assoc_args['name'] && '' !== $assoc_args['width'] && '' !== $assoc_args['height']
		) {
			$name = $assoc_args['name'];
			$width = $assoc_args['width'];
			$height = $assoc_args['height'];
			$crop = $assoc_args['crop'];

			$rokka_helper = new Rokka_Helper();
			$rokka_helper->create_stack( $name, $width, $height, $crop );

			WP_CLI::success( __( 'Stack successfully created or updated.' ) );
		} else {
			WP_CLI::error( __( 'Please provide all required parameters.' ) );
		}
	}

	/**
	 * Create Stacks at Rokka via API without operations.
	 *
	 * ## EXAMPLE
	 * 		wp rokka create_noop_stack --name=banner-large
	 *
	 * @param array $args Arguments from cli-command.
	 * @param array $assoc_args Associative arguments from cli-command.
	 */
	public function create_noop_stack( $args, $assoc_args ) {
		if ( isset( $assoc_args['name'] ) && '' !== $assoc_args['name'] ) {
			$rokka_helper = new Rokka_Helper();
			$rokka_helper->create_noop_stack( $assoc_args['name'] );
			WP_CLI::success( __( 'Stack successfully created or updated.' ) );
		} else {
			WP_CLI::error( __( 'Please provide all required parameters.' ) );
		}
	}
}
