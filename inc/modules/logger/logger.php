<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_Logger {

	public $logs_path;

	public function __construct() {
		$this->logs_path = wp_upload_dir()['basedir'] . '/tfc/logs/';
		$this->check_log_folder();
	}
	/**
	 * Writes a string to the log file
	 * 
	 * @param $text String   String to log
	 * @param $br true|false Add break line, default false
	 */
	public function log( $text = '', $br = false ) {
		$date = $this->get_date_string();

		if ( $text ) {
			error_log( $date . ' - ' . $text . "\n", 3, $this->logs_path . $this->get_filename() );
		} else {
			error_log( '', 3, $this->logs_path . $this->get_filename() );
		}

		if ( $br ) {
			$this->br();
		}
	}

	public function br( $dbg = '' ) {
		error_log( "$dbg\n", 3, $this->logs_path . $this->get_filename() );
	}

	public function check_log_folder() {
		if ( ! file_exists( $this->logs_path ) ) {
			mkdir( $this->logs_path, 0777, true );
		}
	}

	public function get_date_string( $gmt = true ) {
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
	}

	public function get_filename( $gmt = true ) {
		return 'tfc-' . date( 'Y-m-d', current_time( 'timestamp' ) ) . '.log';
	}

	public function create_log_file() {
		$this->log();
	}
}


global $tfc_logger;
$tfc_logger = new TFC_Logger();