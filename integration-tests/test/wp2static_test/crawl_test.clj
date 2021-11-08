(ns wp2static-test.crawl-test
  (:require [clojure.string :as str]
            [clojure.test :refer :all]
            [wp2static-test.test :as test]))

(defn get-crawled-file [path]
  (slurp (str "wordpress/wp-content/uploads/wp2static-crawled-site/" path)))

(deftest test-crawled-site
  (test/with-test-system [_]
    (test/wp-cli! {} "wp2static" "detect")
    (test/wp-cli! {} "wp2static" "crawl")
    (is (str/includes? (get-crawled-file "index.html") "Welcome to WordPress"))
    (is (str/includes? (get-crawled-file "robots.txt") "Sitemap: http://localhost:7000/wp-sitemap.xml"))))
