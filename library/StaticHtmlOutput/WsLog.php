<?php

class WsLog {

  public static function l($text) {
    $wp_uploads_path = $_POST['wp_uploads_path'];
    $working_directory = isset($_POST['workingDirectory']) ? $_POST['workingDirectory'] : $this->wp_uploads_path;

    $log_file_path = $working_directory . '/WP-STATIC-EXPORT-LOG';

    // TODO: create file when initializing the log with header information, reducing each call here


    $src = fopen($log_file_path, (file_exists($log_file_path)) ? 'r+' : 'w');
    $dest = fopen('php://temp', 'w');

    fwrite($dest,  date("Y-m-d h:i:s") . ' ' . $text . PHP_EOL);
    stream_copy_to_stream($src, $dest);
    rewind($dest);
    rewind($src);
    stream_copy_to_stream($dest, $src);
    fclose($src);
    fclose($dest);
  }
}
