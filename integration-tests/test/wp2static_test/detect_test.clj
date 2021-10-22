(ns wp2static-test.crawl-test
  (:require [clojure.string :as str]
            [wp2static-test.core :as core]
            [wp2static-test.test :as test])
  (:use clojure.test))

(def robots-sitemap-slashes "User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: http://localhost:7000//wp-sitemap.xml")

(deftest test-robots-sitemap-slashes
  (testing "robots.txt sitemap URLs with double slashes are processed"
    (test/with-test-system [_]
      (spit "wordpress/robots.txt" robots-sitemap-slashes)
      (core/wp-cli! "wp2static" "detect")
      (core/wp-cli! "wp2static" "crawl")
      (is (str/includes? (get-file "wp-sitemap-posts-post-1.xml") "http://localhost:7000/hello-world/")))))
