(ns wp2static-test.post-process-test
  (:require [clojure.string :as str]
            [wp2static-test.core :as core]
            [wp2static-test.test :as test])
  (:use clojure.test))

(defn get-file [path]
  (slurp (str "wordpress/wp-content/uploads/wp2static-processed-site/" path)))

(deftest test-processed-site
  (test/with-test-system [_]
    (core/wp-cli! "wp2static" "detect")
    (core/wp-cli! "wp2static" "crawl")
    (core/wp-cli! "wp2static" "post_process")
    (let [index (get-file "index.html")]
      (is (str/includes? index "Welcome to WordPress"))
      (testing "Rewrites work"
        (is (str/includes? index "<a href=\"https://example.com/hello-world/\">"))      
        (is (str/includes? (get-file "robots.txt") "Sitemap: https://example.com/wp-sitemap.xml"))))))
