<?php
/**
 * Rokka cli command class.
 *
 * @package rokka-cli-command
 */

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Class Rokka_WP_CLI_Command
 */
class Rokka_WP_CLI_Command extends WP_CLI_Command {

	/**
	 * Creates stack on rokka.
	 *
	 * ## OPTIONS
	 *
	 * --name=<stack-name>
	 * : The name of the stack to create.
	 *
	 * --width=<stack-width>
	 * : The width of the stack to create.
	 *
	 * --height=<stack-height>
	 * : The height of the stack to create.
	 *
	 * [--crop]
	 * : Whether or not the stack should be cropped.
	 * ---
	 * default: false
	 * ---
	 *
	 * [--autoformat]
	 * : Whether or not autoformat should be enabled on the stack.
	 * ---
	 * default: false
	 * ---
	 *
	 * ## EXAMPLE
	 *      wp rokka create_stack --name=banner-large --width=1600 --height=700 --crop --autoformat
	 *
	 * @param array $args Arguments from cli-command.
	 * @param array $assoc_args Associative arguments from cli-command.
	 */
	public function create_stack( $args, $assoc_args ) {
		if ( isset( $assoc_args['name'] ) && isset( $assoc_args['width'] ) && isset( $assoc_args['height'] ) &&
			! empty( $assoc_args['name'] ) && ! empty( $assoc_args['width'] ) && ! empty( $assoc_args['height'] )
		) {
			$name = $assoc_args['name'];
			$width = $assoc_args['width'];
			$height = $assoc_args['height'];
			$crop = (bool) key_exists( 'crop', $assoc_args ) ? $assoc_args['crop'] : false;
			$autoformat = (bool) key_exists( 'autoformat', $assoc_args ) ? $assoc_args['autoformat'] : false;

			try {
				$rokka_helper = new Rokka_Helper();
				if ( $rokka_helper->are_settings_complete() ) {
					WP_CLI::line( sprintf( 'Creating stack %1$s [width: %2$s, height: %3$s, crop: %4$s, autoformat: %5$s]...', $name, $width, $height, ( $crop ? 'true' : 'false' ), ( $autoformat ? 'true' : 'false' ) ) );
					$rokka_helper->create_stack( $name, $width, $height, $crop, true, $autoformat );
					WP_CLI::success( 'Stack successfully created or updated.' );
				} else {
					WP_CLI::warning( 'Please configure rokka in settings before creating new stack.' );
				}
			} catch ( Exception $e ) {
				WP_CLI::error( 'rokka-API threw an exception: ' . $e->getMessage() );
			}
		} else {
			WP_CLI::error( 'Please provide all required parameters.' );
		}
	}

	/**
	 * Creates noop stack (full size stack) on rokka.
	 *
	 * ## OPTIONS
	 *
	 * --name=<stack-name>
	 * : The name of the stack to create.
	 *
	 * ## EXAMPLE
	 *      wp rokka create_noop_stack --name=full
	 *
	 * @param array $args Arguments from cli-command.
	 * @param array $assoc_args Associative arguments from cli-command.
	 */
	public function create_noop_stack( $args, $assoc_args ) {
		if ( isset( $assoc_args['name'] ) && ! empty ( $assoc_args['name'] ) ) {
			try {
				$rokka_helper = new Rokka_Helper();
				if ( $rokka_helper->are_settings_complete() ) {
					WP_CLI::line( sprintf( 'Creating noop stack %1$s...', $assoc_args['name'] ) );
					$rokka_helper->create_noop_stack( $assoc_args['name'] );
					WP_CLI::success( 'Stack successfully created or updated.' );
				} else {
					WP_CLI::warning( 'Please configure rokka in settings before creating noop stack.' );
				}
			} catch ( Exception $e ) {
				WP_CLI::error( 'rokka-API threw an exception: ' . $e->getMessage() );
			}
		} else {
			WP_CLI::error( 'Please provide all required parameters.' );
		}
	}
}
