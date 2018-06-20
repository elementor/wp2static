<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Page class, for tracking the status of pages / static files
 */
class Page extends Model {

	/** @const */
	public static $processable_status_codes = array(
		200, 301, 302, 303, 307, 308
	);

	/** @const */
	protected static $table_name = 'pages';

	/** @const */
	protected static $columns = array(
		'id'                  => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
		'found_on_id'         => 'BIGINT(20) UNSIGNED NULL',
		'url'                 => 'VARCHAR(255) NOT NULL',
		'redirect_url'        => 'TEXT NULL',
		'file_path'           => 'VARCHAR(255) NULL',
		'http_status_code'    => 'SMALLINT(20) NULL',
		'content_type'        => 'VARCHAR(255) NULL',
		'content_hash'        => 'BINARY(20) NULL',
		'error_message'       => 'VARCHAR(255) NULL',
		'status_message'      => 'VARCHAR(255) NULL',
		'last_checked_at'     => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'last_modified_at'    => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'last_transferred_at' => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'created_at'          => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'updated_at'          => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'"
	);

	/** @const */
	protected static $indexes = array(
		'PRIMARY KEY  (id)',
		'KEY url (url)',
		'KEY last_checked_at (last_checked_at)',
		'KEY last_modified_at (last_modified_at)',
		'KEY last_transferred_at (last_transferred_at)'
	);

	/** @const */
	protected static $primary_key = 'id';

	/**
	 * Get the number of pages for each group of status codes, e.g. 1xx, 2xx, 3xx
	 * @return array Assoc. array of status code to number of pages, e.g. '2' => 183
	 */
	public static function get_http_status_codes_summary() {
		global $wpdb;

		$query = 'SELECT LEFT(http_status_code, 1) AS status, COUNT(*) AS count';
		$query .= ' FROM ' . self::table_name();
		$query .= ' GROUP BY LEFT(http_status_code, 1)';
		$query .= ' ORDER BY status';

		$rows = $wpdb->get_results(
			$query,
			ARRAY_A
		);

		$http_codes = array( '1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0 );
		foreach ( $rows as $row ) {
			$http_codes[ $row['status'] ] = $row['count'];
		}

		return $http_codes;
	}

	/**
	 * Return the static page that this page belongs to (if any)
	 * @return Page The parent Page
	 */
	public function parent_static_page() {
		return self::query()->find_by( 'id', $this->found_on_id );
	}

	/**
	 * Check if the hash for the content matches the prior hash for the page
	 * @param  string  $content The content of the page/file
	 * @return boolean          Is the hash a match?
	 */
	public function is_content_identical( $sha1 ) {
		return $sha1 === $this->content_hash;
	}

	/**
	 * Set the hash for the content and update the last_modified_at value
	 * @param string $content The content of the page/file
	 */
	public function set_content_hash( $sha1 ) {
		$this->content_hash = $sha1;
		$this->last_modified_at = Util::formatted_datetime();
	}

	/**
	 * Set an error message
	 *
	 * An error indicates that something bad happened when fetching the page, or
	 * saving the page, or during some other activity related to the page.
	 * @param string $message The error message
	 */
	public function set_error_message( $message ) {
		if ( $this->error_message ) {
			$this->error_message = $this->error_message . '; ' . $message;
		} else {
			$this->error_message = $message;
		}
	}

	/**
	 * Set a status message
	 *
	 * A status message is used to indicate things that happened to the page
	 * that weren't errors, such as not following links or not saving the page.
	 * @param string $message The status message
	 */
	public function set_status_message( $message ) {
		if ( $this->status_message ) {
			$this->status_message = $this->status_message . '; ' . $message;
		} else {
			$this->status_message = $message;
		}
	}

	public function is_type( $content_type ) {
		return stripos( $this->content_type, $content_type ) !== false;
	}
}
