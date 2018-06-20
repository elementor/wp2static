<?php
namespace Simply_Static;

require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

class Create_Zip_Archive_Task extends Task {

	/**
	 * @var string
	 */
	protected static $task_name = 'create_zip_archive';

	public function perform() {
		$download_url = $this->create_zip();
		if ( is_wp_error( $download_url ) ) {
			return $download_url;
		} else {
			$message = __( 'ZIP archive created: ', 'simply-static' );
			$message .= ' <a href="' . $download_url . '">' . __( 'Click here to download', 'simply-static' ) . '</a>';
			$this->save_status_message( $message );
			return true;
		}
	}

	/**
	 * Create a ZIP file using the archive directory
	 * @return string|\WP_Error $temporary_zip The path to the archive zip file
	 */
	public function create_zip() {
		$archive_dir = $this->options->get_archive_dir();

		$zip_filename = untrailingslashit( $archive_dir ) . '.zip';
		$zip_archive = new \PclZip( $zip_filename );

		Util::debug_log( "Fetching list of files to include in zip" );
		$files = array();
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $archive_dir, \RecursiveDirectoryIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file_name => $file_object ) {
			$files[] = realpath( $file_name );
		}

		Util::debug_log( "Creating zip archive" );
		if ( $zip_archive->create( $files, PCLZIP_OPT_REMOVE_PATH, $archive_dir ) === 0 ) {
			return new \WP_Error( 'create_zip_failed', __( 'Unable to create ZIP archive', 'simply-static' ) );
		}

		$download_url = get_admin_url( null, 'admin.php' ) . '?' . Plugin::SLUG . '_zip_download=' . basename( $zip_filename );

		return $download_url;
	}

}
