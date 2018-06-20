<?php
namespace Simply_Static;

class Transfer_Files_Locally_Task extends Task {

	/**
	 * @var string
	 */
	protected static $task_name = 'transfer_files_locally';


	/**
	 * Copy a batch of files from the temp dir to the destination dir
	 * @return boolean true if done, false if not done
	 */
	public function perform() {
		$local_dir = $this->options->get( 'local_dir' );

		list( $pages_processed, $total_pages ) = $this->copy_static_files( $local_dir );

		if ( $pages_processed !== 0 ) {
			$message = sprintf( __( "Copied %d of %d files", 'simply-static' ), $pages_processed, $total_pages );
			$this->save_status_message( $message );
		}

		if ( $pages_processed >= $total_pages ) {
			if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
				$destination_url = trailingslashit( $this->options->get_destination_url() );
				$message = __( 'Destination URL:', 'simply-static' ) . ' <a href="' . $destination_url .'" target="_blank">' . $destination_url . '</a>';
				$this->save_status_message( $message, 'destination_url' );
			}
		}

		// return true when done (no more pages)
		return $pages_processed >= $total_pages;

	}

	/**
	* Copy temporary static files to a local directory
	* @param  string $destination_dir The directory to put the files
	* @return array (# pages processed, # pages remaining)
	*/
	public function copy_static_files( $destination_dir ) {
		$batch_size = 100;

		$archive_dir = $this->options->get_archive_dir();
		$archive_start_time = $this->options->get( 'archive_start_time' );

		// TODO: also check for recent modification time
		// last_modified_at > ? AND
		$static_pages = Page::query()
			->where( "file_path IS NOT NULL" )
			->where( "file_path != ''" )
			->where( "( last_transferred_at < ? OR last_transferred_at IS NULL )", $archive_start_time )
			->limit( $batch_size )
			->find();
		$pages_remaining = count( $static_pages );
		$total_pages = Page::query()
			->where( "file_path IS NOT NULL" )
			->where( "file_path != ''" )
			->count();
		$pages_processed = $total_pages - $pages_remaining;
		Util::debug_log( "Total pages: " . $total_pages . '; Pages remaining: ' . $pages_remaining );

		while ( $static_page = array_shift( $static_pages ) ) {
			$path_info = Util::url_path_info( $static_page->file_path );
			$path = $destination_dir . $path_info['dirname'];
			$create_dir = wp_mkdir_p( $path );
			if ( $create_dir === false ) {
				Util::debug_log( "Cannot create directory: " . $destination_dir . $path_info['dirname'] );
				$static_page->set_error_message( 'Unable to create destination directory' );
			} else {
				chmod( $path, 0755 );
				$origin_file_path = $archive_dir . $static_page->file_path;
				$destination_file_path = $destination_dir . $static_page->file_path;

				// check that destination file doesn't exist OR exists but is writeable
				if ( ! file_exists( $destination_file_path ) || is_writable( $destination_file_path ) ) {
					$copy = copy( $origin_file_path, $destination_file_path );
					if ( $copy === false ) {
						Util::debug_log( "Cannot copy " . $origin_file_path .  " to " . $destination_file_path );
						$static_page->set_error_message( 'Unable to copy file to destination' );
					}
				} else {
					Util::debug_log( "File exists and is unwriteable: " . $destination_file_path );
					$static_page->set_error_message( 'Destination file exists and is unwriteable' );
				}
			}

			$static_page->last_transferred_at = Util::formatted_datetime();
			$static_page->save();
		}

		return array( $pages_processed, $total_pages );
	}

}
