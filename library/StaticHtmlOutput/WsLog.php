<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

class WsLog
{
  public static function l($text) {
    $tmp_var_to_hold_return_array = wp_upload_dir();
    $file = $tmp_var_to_hold_return_array['basedir'] . '/WP-STATIC-EXPORT-LOG';

    $src = fopen($file, (file_exists($file)) ? 'r+' : 'w');
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
