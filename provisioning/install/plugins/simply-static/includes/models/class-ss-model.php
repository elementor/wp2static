<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Model class
 *
 * Represents a single database table with accessors for finding, creating, and
 * updating records.
 */
class Model {

	/**
	 * The name of the table (prefixed with the name of the plugin)
	 * @var string
	 */
	protected static $table_name = null;

	/**
	 * A list of the columns for the model
	 *
	 * In the format of 'col_name' => 'col_definition', e.g.
	 *     'id' => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'
	 * @var array
	 */
	protected static $columns = array();

	/**
	 * A list of the indexes for the model
	 *
	 * In the format of 'index_name' => 'index_def', e.g.
	 *     'url' => 'url'
	 * @var array
	 */
	protected static $indexes = array();

	/**
	 * The name of the primary key for the model
	 * @var string
	 */
	protected static $primary_key = null;

	/**************************************************************************/

	/**
	 * The stored data for this instance of the model.
	 * @var array
	 */
	private $data = array();

	/**
	 * Track if this record has had changed made to it
	 * @var boolean
	 */
	private $dirty_fields = array();

	/**
	 * Retrieve the value of a field for the model
	 *
	 * Returns an exception if you try to retrieve a field that isn't set.
	 * @param  string $field_name The name of the field to retrieve
	 * @return mixed              The value for the field
	 */
	public function __get( $field_name ) {
		if ( ! array_key_exists( $field_name, $this->data ) ) {
			throw new \Exception( 'Undefined variable for ' . get_called_class() );
		} else {
			return $this->data[ $field_name ];
		}
	}

	/**
	 * Set the value of a field for the model
	 *
	 * Returns an exception if you try to set a field that isn't one of the
	 * model's columns.
	 * @param string $field_name  The name of the field to set
	 * @param mixed  $field_value The value for the field
	 * @return mixed              The value of the field that was set
	 */
	public function __set( $field_name, $field_value ) {
		if ( ! array_key_exists( $field_name, static::$columns ) ) {
			throw new \Exception( 'Column doesn\'t exist for ' . get_called_class() );
		} else {
			if ( ! array_key_exists( $field_name, $this->data ) || $this->data[ $field_name ] !== $field_value ) {
				array_push( $this->dirty_fields, $field_name );
			}
			return $this->data[ $field_name ] = $field_value;
		}
	}

	/**
	 * Returns the name of the table
	 *
	 * Note that MySQL doesn't allow anything other than alphanumerics,
	 * underscores, and $, so dashes in the slug are replaced with underscores.
	 * @return string The name of the table
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'simply_static_' . static::$table_name;
	}

	/**
	 * Used for finding models matching certain criteria
	 * @return Simply_Static\Query
	 */
	public static function query()
	{
		$query = new Query( get_called_class() );
		return $query;
	}

	/**
	 * Initialize an instance of the class and set its attributes
	 * @param  array $attributes Array of attributes to set for the class
	 * @return static            An instance of the class
	 */
	public static function initialize( $attributes ) {
		$obj = new static();
		foreach ( array_keys( static::$columns ) as $column ) {
			$obj->data[ $column ] = null;
		}
		$obj->attributes( $attributes );
		return $obj;
	}

	/**
	 * Set the attributes of the model
	 * @param  array $attributes Array of attributes to set
	 * @return static            An instance of the class
	 */
	public function attributes( $attributes ) {
		foreach ( $attributes as $name => $value ) {
			$this->$name = $value;
		}
		return $this;
	}

	/**
	 * Save the model to the database
	 *
	 * If the model is new a record gets created in the database, otherwise the
	 * existing record gets updated.
	 * @param  array $attributes Array of attributes to set
	 * @return boolean           An instance of the class
	 */
	public function save() {
		global $wpdb;

		// autoset created_at/updated_at upon save
		if ( $this->created_at === null ) {
			$this->created_at = Util::formatted_datetime();
		}
		$this->updated_at = Util::formatted_datetime();

		// If we haven't changed anything, don't bother updating the DB, and
		// return that saving was successful.
		if ( empty( $this->dirty_fields ) ) {
			return true;
		} else {
			// otherwise, create a new array with just the fields we're updating,
			// then set the dirty fields back to empty
			$fields = array_intersect_key( $this->data, array_flip( $this->dirty_fields ) );
			$this->dirty_fields = array();
		}

		if ( $this->exists() ) {
			$primary_key = static::$primary_key;
			$rows_updated = $wpdb->update( self::table_name(), $fields, array( $primary_key => $this->$primary_key ) );
			return $rows_updated !== false;
		} else {
			$rows_updated = $wpdb->insert( self::table_name(), $fields );
			if ( $rows_updated === false ) {
				return false;
			} else {
				$this->id = $wpdb->insert_id;
				return true;
			}
		}
	}

	/**
	 * Check if the model exists in the database
	 *
	 * Technically this is checking whether the model has its primary key set.
	 * If it is set, we assume the record exists in the database.
	 * @return boolean Does this model exist in the db?
	 */
	public function exists() {
		$primary_key = static::$primary_key;
		return $this->$primary_key !== null;
	}

	/**
	 * Create or update the table for the model
	 *
	 * Uses the static::$table_name and loops through all of the columns in
	 * static::$columns and the indexes in static::$indexes to create a SQL
	 * query for creating the table.
	 *
	 * http://wordpress.stackexchange.com/questions/78667/dbdelta-alter-table-syntax
	 * @return void
	 */
	public static function create_or_update_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$sql = 'CREATE TABLE ' . self::table_name() . ' (' . "\n";

		foreach ( static::$columns as $column_name => $column_definition ) {
			$sql .= $column_name . ' ' . $column_definition . ', ' . "\n";
		}
		foreach ( static::$indexes as $index ) {
			$sql .= $index . ', ' . "\n";
		}

		// remove trailing newline
		$sql = rtrim( $sql, "\n" );
		// remove trailing comma
		$sql = rtrim( $sql, ', ' );
		$sql .= "\n" . ') ' . "\n" . $charset_collate;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Drop the table for the model
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::table_name() );
	}
}
