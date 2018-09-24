<?php
/**
 * WsLog
 *
 * @package WP2Static
 */

class WsLog {

  public static function l($text) {
    $wp_uploads_path = $_POST['wp_uploads_path'];
    $working_directory = isset($_POST['workingDirectory']) ? $_POST['workingDirectory'] : $this->wp_uploads_path;

    $log_file_path = $working_directory . '/WP-STATIC-EXPORT-LOG';

    $myfile = fopen($log_file_path, "a") or die("Unable to open file!");
    fwrite($myfile, $text . PHP_EOL);
    fclose($myfile);
  }
}
