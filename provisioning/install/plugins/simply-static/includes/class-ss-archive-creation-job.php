<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( ABSPATH . 'wp-admin/includes/admin.php' );

/**
 * Simply Static archive manager class
 */
class Archive_Creation_Job extends \WP_Background_Process {

	/**
	 * The name of the job/action
	 * @var string
	 */
	protected $action = 'archive_creation_job';

	/**
	 * The name of the task currently being processed
	 * @var string
	 */
	protected $current_task = null;

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	/**
	 * Array containing the list of tasks to process
	 * @var array
	 */
	protected $task_list = array();

	/**
	 * Performs initializion of the options structure
	 * @param string $option_key The options key name
	 */
	public function __construct() {
		$this->options = Options::instance();
		$this->task_list = apply_filters( 'simplystatic.archive_creation_job.task_list', array(), $this->options->get( 'delivery_method' ) );

		if ( ! $this->is_job_done() ) {
			register_shutdown_function( array( $this, 'shutdown_handler' ) );
		}

		parent::__construct();
	}

	/**
	 * Helper method for starting the Archive_Creation_Job
	 * @return boolean true if we were able to successfully start generating an archive
	 */
	public function start() {
		if ( $this->is_job_done() ) {
			Util::debug_log( "Starting a job; no job is presently running" );
			Util::debug_log( "Here's our task list: " . implode( ', ', $this->task_list ) );

			global $blog_id;

			$first_task = $this->task_list[0];
			$archive_name = join( '-', array( Plugin::SLUG, $blog_id, time() ) );

			$this->options
				->set( 'archive_status_messages', array() )
				->set( 'archive_name', $archive_name )
				->set( 'archive_start_time', Util::formatted_datetime() )
				->set( 'archive_end_time', null )
				->save();

			Util::debug_log( "Pushing first task to queue: " . $first_task );

			$this->push_to_queue( $first_task )
				->save()
				->dispatch();

			return true;
		} else {
			Util::debug_log( "Not starting; we're already in the middle of a job" );
			// looks like we're in the middle of creating an archive...
			return false;
		}
	}

	/**
	 * Perform the task at hand
	 *
	 * The way Archive_Creation_Job works is by taking a task name, performing
	 * that task, and then either (a) returnning the current task name to
	 * continue processing it (e.g. fetch more urls), (b) returning the next
	 * task name if we're done with the current one, or (c) returning false if
	 * we're done with our job, which then runs complete().
	 *
	 * @param string        $task Task name to process
	 * @return false|string       task name to process, or false if done
	 */
	protected function task( $task_name ) {
		$this->set_current_task( $task_name );

		Util::debug_log( "Current task: " . $task_name );

		// convert 'an_example' to 'An_Example_Task'
		$class_name = 'Simply_Static\\' . ucwords( $task_name ) . '_Task';

		// this shouldn't ever happen, but just in case...
		if ( ! class_exists( $class_name ) ) {
			$this->save_status_message( "Class doesn't exist: " . $class_name, 'error' );
			return false;
		}

		$task = new $class_name();

		// attempt to perform the task
		try {
			Util::debug_log( "Performing task: " . $task_name );
			$is_done = $task->perform();
		} catch ( \Error $e ) {
			Util::debug_log( "Caught an error" );
			return $this->error_occurred( $e );
		} catch ( \Exception $e ) {
			Util::debug_log( "Caught an exception" );
			return $this->exception_occurred( $e );
		}

		if ( is_wp_error( $is_done ) ) {
			// we've hit an error, time to quit
			Util::debug_log( "We encountered a WP_Error" );
			return $this->error_occurred( $is_done );
		} else if ( $is_done === true ) {
			// finished current task, try to find the next one
			$next_task = $this->find_next_task();
			if ( $next_task === null ) {
				Util::debug_log( "This task is done and there are no more tasks, time to complete the job" );
				// we're done; returning false to remove item from queue
				return false;
			} else {
				Util::debug_log( "We've found our next task: " . $next_task );
				// start the next task
				return $next_task;
			}
		} else { // $is_done === false
			Util::debug_log( "We're not done with the " . $task_name . " task yet" );
			// returning current task name to continue processing
			return $task_name;
		}

		Util::debug_log( "We shouldn't have gotten here; returning false to remove the " . $task_name . " task from the queue" );
		return false; // remove item from queue
	}

	/**
	 * This is run at the end of the job, after task() has returned false
	 * @return void
	 */
	protected function complete() {
		Util::debug_log( "Completing the job" );

		$this->set_current_task( 'done' );

		$end_time = Util::formatted_datetime();
		$start_time = $this->options->get( 'archive_start_time' );
		$duration = strtotime( $end_time ) - strtotime( $start_time );
		$time_string = gmdate( "H:i:s", $duration );

		$this->options->set( 'archive_end_time', $end_time );

		$this->save_status_message( sprintf( __( 'Done! Finished in %s', 'simply-static' ), $time_string ) );
		parent::complete();
	}


	/**
	 * Cancel the currently running job
	 * @return void
	 */
	public function cancel() {
		if ( ! $this->is_job_done() ) {
			Util::debug_log( "Cancelling job; job is not done" );

			if ( $this->is_queue_empty() ) {
				Util::debug_log( "The queue is empty, pushing the cancel task" );
				// generally the queue shouldn't be empty when we get a request to
				// cancel, but if we do, add a cancel task and start processing it.
				// that should get the job back into a state where it can be
				// started again.
				$this->push_to_queue( 'cancel' )
					->save();
			} else {
				Util::debug_log( "The queue isn't empty; overwriting current task with a cancel task" );
				// unlock the process so that we can force our cancel task to process
				$this->unlock_process();

				// overwrite whatever the current task is with the cancel task
				$batch = $this->get_batch();
				$batch->data = array( 'cancel' );
				$this->update( $batch->key, $batch->data );
			}

			$this->dispatch();
		} else {
			Util::debug_log( "Can't cancel; job is done" );
		}
	}

	/**
	 * Is the job done?
	 * @return boolean True if done, false if not
	 */
	public function is_job_done() {
		$start_time = $this->options->get( 'archive_start_time' );
		$end_time = $this->options->get( 'archive_end_time' );
		// we're done if the start and end time are null (never run) or if
		// the start and end times are both set
		return ( $start_time == null && $end_time == null ) || ( $start_time != null && $end_time != null);
	}

	/**
	 * Return the current task
	 * @return string The current task
	 */
	public function get_current_task() {
		return $this->current_task;
	}

	/**
	 * Set the current task name
	 * @param stroing $task_name The name of the current task
	 */
	protected function set_current_task( $task_name ) {
		$this->current_task = $task_name;
	}

	/**
	 * Find the next task on our task list
	 * @return string|null       The name of the next task, or null if none
	 */
	protected function find_next_task() {
		$task_name = $this->get_current_task();
		$index = array_search( $task_name, $this->task_list );
		if ( $index === false ) {
			return null;
		}

		$index += 1;
		if ( ( $index ) >= count( $this->task_list ) ) {
			return null;
		} else {
			return $this->task_list[ $index ];
		}
	}

	/**
	 * Add a message to the array of status messages for the job
	 *
	 * Providing a unique key for the message is optional. If one isn't
	 * provided, the state_name will be used. Using the same key more than once
	 * will overwrite previous messages.
	 *
	 * @param  string $message Message to display about the status of the job
	 * @param  string $key     Unique key for the message
	 * @return void
	 */
	protected function save_status_message( $message, $key = null ) {
		$task_name = $key ?: $this->get_current_task();
		$messages = $this->options->get( 'archive_status_messages' );
		Util::debug_log( 'Status message: [' . $task_name . '] ' . $message );

		$messages = Util::add_archive_status_message( $messages, $task_name, $message );

		$this->options
			->set( 'archive_status_messages', $messages )
			->save();
	}

	/**
	 * Add a status message about the exception and cancel the job
	 * @param  Exception $exception The exception that occurred
	 * @return void
	 */
	protected function exception_occurred( $exception ) {
		Util::debug_log( "An exception occurred: " . $exception->getMessage() );
		Util::debug_log( $exception );
		$message = sprintf( __( "An exception occurred: %s", 'simply-static' ), $exception->getMessage() );
		$this->save_status_message( $message, 'error' );
		return 'cancel';
	}

	/**
	 * Add a status message about the error and cancel the job
	 * @param  WP_Error $wp_error The error that occurred
	 * @return void
	 */
	protected function error_occurred( $wp_error ) {
		Util::debug_log( "An error occurred: " . $wp_error->get_error_message() );
		Util::debug_log( $wp_error );
		$message = sprintf( __( "An error occurred: %s", 'simply-static' ), $wp_error->get_error_message() );
		$this->save_status_message( $message, 'error' );
		return 'cancel';
	}

	/**
	 * Shutdown handler for fatal error reporting
	 * @return void
	 */
	public function shutdown_handler() {
		// Note: this function must be public in order to function properly.
		$error = error_get_last();
		// only trigger on actual errors, not warnings or notices
		if ( $error && in_array( $error['type'], array( E_ERROR, E_CORE_ERROR, E_USER_ERROR ) ) ) {
			$this->clear_scheduled_event();
			$this->unlock_process();
			$this->cancel_process();

			$end_time = Util::formatted_datetime();
			$this->options
				->set( 'archive_end_time', $end_time )
				->save();

			$error_message = '(' . $error['type'] . ') ' . $error['message'];
			$error_message .= ' in <b>' . $error['file'] . '</b>';
			$error_message .= ' on line <b>' . $error['line'] . '</b>';

			$message = sprintf( __( "Error: %s", 'simply-static' ), $error_message );
			Util::debug_log( $message );
			$this->save_status_message( $message, 'error' );
		}
	}

 }
