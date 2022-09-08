<?php

/**
 * Run SSA logic with WP_CLI.
 */
class SSA_Cli_Commands {

	/**
	 * Import a SSA export file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : The absolute path to the export file to be imported.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ssa import https://test-site.ssa.rocks/wp-content/uploads/ssa/export/ssa-export-2021-09-14T13-28-31.json
	 *
	 * @when after_wp_load
	 */
	public function import( $args, $assoc_args ) {
		$path = isset( $args[0] ) ? $args[0] : null;
		if ( empty( $path ) ) {
			WP_CLI::error( __( 'Please provide a valid file path or URL.', 'simply-schedule-appointments' ) );
		}

		$is_url = filter_var( $path, FILTER_VALIDATE_URL );
		if ( $is_url ) {
			$import_content = wp_safe_remote_get( $path );

			if ( is_wp_error( $import_content ) ) {
				WP_CLI::error( __( 'There was an error retrieving the file.', 'simply-schedule-appointments' ) );
			}

			$json = wp_remote_retrieve_body( $import_content );
		} else {
			if ( ! file_exists( $path ) ) {
				WP_CLI::error( __( 'Could not find the file.', 'simply-schedule-appointments' ) );
			}
			if ( ! is_readable( $path ) ) {
				WP_CLI::error( __( 'Could not read the file.', 'simply-schedule-appointments' ) );
			}

			$json = file_get_contents( $path );

			if ( empty( $json ) ) {
				WP_CLI::error( __( 'The file is empty.', 'simply-schedule-appointments' ) );
			}
		}

		WP_CLI::log( __( 'Importing...', 'simply-schedule-appointments' ) );

		// verify if JSON data is valid.
		$decoded = json_decode( $json, true );

		if ( ! is_object( $decoded ) && ! is_array( $decoded ) ) {
			WP_CLI::error( __( 'Invalid data format.', 'simply-schedule-appointments' ) );
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			WP_CLI::error( __( 'Invalid data format.', 'simply-schedule-appointments' ) );
		}

		$import = ssa()->support_status->import_data( $decoded );

		// if any error happens while trying to import appointment type data, return.
		if ( is_wp_error( $import ) ) {
			WP_CLI::error( $import->get_error_messages() );
		}

		// everything was successfully imported.
		WP_CLI::success( __( 'Data successfully imported!', 'simply-schedule-appointments' ) );
	}
}

$instance = new SSA_Cli_Commands();
WP_CLI::add_command( 'ssa', $instance );
