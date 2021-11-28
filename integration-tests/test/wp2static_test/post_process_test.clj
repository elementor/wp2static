(ns wp2static-test.post-process-test
  (:require [clojure.string :as str]
            [clojure.test :refer :all]
            [wp2static-test.test :as test]))

(defn get-processed-file [wp path]
  (slurp (str (get-in wp [:paths :uploads]) "/wp2static-processed-site/" path)))

(deftest test-processed-site
  (test/with-test-system [system {}]
    (doseq [wp (vals (:wordpresses system))
            :let [wp-cli! #(apply test/wp-cli! {:path (get-in wp [:paths :cli])} %&)]]
      (test/testing [wp "Post-processing works"]
        (wp-cli! "wp2static" "detect")
        (wp-cli! "wp2static" "crawl")
        (wp-cli! "wp2static" "post_process")
        (let [index (get-processed-file wp "index.html")]
          (is (str/includes? index "Welcome to WordPress"))
          (testing "& URL rewriting works"
            (is (str/includes? index "<a href=\"https://example.com/hello-world/\">"))
            (when (get-in wp [:features :sitemaps?])
              (is (str/includes? (get-processed-file wp "robots.txt") "Sitemap: https://example.com/wp-sitemap.xml")))))))))
