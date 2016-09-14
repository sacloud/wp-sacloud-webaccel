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
            if (!sacloud_webaccel_auth()) {
                $message = __("API Token Authentication error", "wp-sacloud-webaccel");
                WP_CLI::error($message);
            }

            add_action("sacloud_webaccel_call_purge_api" ,array(get_called_class(), 'purge_log'));

            sacloud_webaccel_true_purge_all();
            $message = __( 'Purged Everything!' , "wp-sacloud-webaccel" );
			WP_CLI::success( $message );
		}


		public static function purge_log($targets){
            foreach($targets as $t){
                WP_CLI::log(sprintf("    %s", $t));
            }
        }
	}

}