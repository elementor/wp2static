(ns wp2static-test.crawl-test
  (:require [clojure.string :as str]
            [wp2static-test.core :as core]
            [wp2static-test.test :as test])
  (:use clojure.test))

(defn get-file [path]
  (slurp (str "wordpress/wp-content/uploads/wp2static-crawled-site/" path)))

(deftest test-crawled-site
  (test/with-test-system [_]
    (core/wp-cli! "wp2static" "detect")
    (core/wp-cli! "wp2static" "crawl")
    (is (str/includes? (get-file "index.html") "Welcome to WordPress"))
    (is (str/includes? (get-file "robots.txt") "Sitemap: http://localhost:7000/wp-sitemap.xml"))))
