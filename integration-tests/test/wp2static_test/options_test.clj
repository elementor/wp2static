(ns wp2static-test.options-test
  (:require [clojure.string :as str]
            [clojure.test :refer :all]
            [wp2static-test.test :as test]))

(def options-test-filters
  "<?php

/**
 * Plugin Name: Options Test
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

function processQueueImmediately_filter ( $val ) {
    return '1';
}
add_filter( 'wp2static_option_processQueueImmediately', 'processQueueImmediately_filter' );")

(deftest test-option-filters
  (test/with-test-system [_]
    (testing "wp2static_option_* filters work"
      (is (= "0" (-> (test/wp-cli! {} "wp2static" "options" "get" "processQueueImmediately")
                   :out
                   str/trim)))
      (try
        (test/sh! {} "mkdir" "wordpress/wp-content/plugins/options-test")
        (spit "wordpress/wp-content/plugins/options-test/options-test.php"
          options-test-filters)
        (test/wp-cli! {} "plugin" "activate" "options-test")
        (is (= "1" (-> (test/wp-cli! {} "wp2static" "options" "get" "processQueueImmediately")
                     :out
                     str/trim)))
        (test/wp-cli! {} "plugin" "deactivate" "options-test")
        (finally
          (test/sh! {} "rm" "-rf" "wordpress/wp-content/plugins/options-test"))))))
