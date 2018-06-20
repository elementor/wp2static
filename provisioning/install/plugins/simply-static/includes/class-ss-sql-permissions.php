<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static SQL Privilege Checker
 *
 * Checks to ensure that the MySQL has permissions needed for Simply Static.
 */
class Sql_Permissions {

	/**
	 * Singleton instance
	 * @var Simply_Static\Sql_Permissions
	 */
	protected static $instance = null;

	/**
	 * SQL permissions that a user could have
	 * @var array
	 */
	private $permissions = array(
		'select' => false,
		'update' => false,
		'insert' => false,
		'delete' => false,
		'alter'  => false,
		'create' => false,
		'drop' 	 => false
	);

	/**
	 * Disable usage of "new"
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Return an instance of Simply_Static\Sql_Permissions
	 * @return Simply_Static\Sql_Permissions
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();

			global $wpdb;
			$rows = $wpdb->get_results( 'SHOW GRANTS FOR current_user()', ARRAY_N );

			// Loop through all of the grants and set permissions to true where
			// we're able to find them.
			foreach ( $rows as $row ) {
				// Find the database name
				preg_match( '/GRANT (.+) ON (.+) TO/', $row[0], $matches );
				// Removing backticks and backslashes for easier matching
				$db_name = preg_replace('/[\\\`]/', '', $matches[2]);

				if ( substr( $db_name, -3 ) == '%.*' ) {
					// Check for a wildcard match on the database
					$db_name = substr( $db_name, 0, -3 );
					$db_name_match = ( stripos( $wpdb->dbname, $db_name ) === 0 );
				} else {
					// Check for matches for all dbs (*.*) or this specific WP db
					$db_name_match = in_array( $db_name, array( '*.*', $wpdb->dbname . '.*' ) );
				}

				if ( $db_name_match ) {
					foreach ( explode( ',', $matches[1] ) as $permission ) {
						$permission = str_replace( ' ', '_', trim( strtolower( $permission ) ) );
						if ( $permission === 'all_privileges' ) {
							foreach ( self::$instance->permissions as $key => $value ) {
								self::$instance->permissions[ $key ] = true;
							}
						}
						self::$instance->permissions[ $permission ] = true;
					}
				}
			}
		}

		return self::$instance;
	}

	/**
	 * Check if the MySQL user is able to perform the provided permission
	 */
	public function can( $permission ) {
		return ( isset( $this->permissions[ $permission ] ) && $this->permissions[ $permission ] === true );
	}
}
