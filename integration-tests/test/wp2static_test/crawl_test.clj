(ns wp2static-test.crawl-test
  (:require [clojure.string :as str]
            [clojure.test :refer :all]
            [wp2static-test.test :as test]))

(defn get-crawled-file [wp path]
  (slurp (str (get-in wp [:paths :uploads]) "/wp2static-crawled-site/" path)))

(deftest test-crawled-site
  (test/with-test-system [system {}]
    (doseq [wp (vals (:wordpresses system))
            :let [wp-cli! #(apply test/wp-cli! {:path (get-in wp [:paths :cli])} %&)]]
      (test/testing [wp "Crawling works"]
        (wp-cli! "wp2static" "detect")
        (wp-cli! "wp2static" "crawl")
        (is (str/includes? (get-crawled-file wp "index.html")
              "Welcome to WordPress"))
        (is (str/includes? (get-crawled-file wp "robots.txt")
              (str "Disallow: " (get-in wp [:paths :site]) "wp-admin/")))))))
