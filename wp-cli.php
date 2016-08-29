<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Sacloud_WebAccel_WP_CLI_Command' ) ) {

    /**
     * Manage SakuraCloud-WebAccelerator
     */
	class Sacloud_WebAccel_WP_CLI_Command extends WP_CLI_Command {

		/**
		 * Subcommand to purge all cache from WebAccelerator
		 *
		 * Examples:
		 * wp sacloud-webaccel purge-all
		 *
		 * @subcommand purge-all
		 */
		public function purge_all( $args, $assoc_args ) {
            sacloud_webaccel_true_purge_all();
            $message = __( 'Purged Everything!' );
			WP_CLI::success( $message );
		}

	}

}