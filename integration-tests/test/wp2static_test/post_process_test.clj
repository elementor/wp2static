(ns wp2static-test.post-process-test
  (:require [clojure.string :as str]
            [clojure.test :refer :all]
            [wp2static-test.test :as test]))

(defn get-processed-file [path]
  (slurp (str "wordpress/wp-content/uploads/wp2static-processed-site/" path)))

(deftest test-processed-site
  (test/with-test-system [_]
    (test/wp-cli! {} "wp2static" "detect")
    (test/wp-cli! {} "wp2static" "crawl")
    (test/wp-cli! {} "wp2static" "post_process")
    (let [index (get-processed-file "index.html")]
      (is (str/includes? index "Welcome to WordPress"))
      (testing "Rewrites work"
        (is (str/includes? index "<a href=\"https://example.com/hello-world/\">"))      
        (is (str/includes? (get-processed-file "robots.txt") "Sitemap: https://example.com/wp-sitemap.xml"))))))
