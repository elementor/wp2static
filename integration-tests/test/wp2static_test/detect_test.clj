(ns wp2static-test.detect-test
  (:require [clojure.string :as str]
            [clojure.test :refer :all]
            [wp2static-test.test :as test]))

(defn get-crawled-file [path]
  (slurp (str "wordpress/wp-content/uploads/wp2static-crawled-site/" path)))

(def robots-sitemap-404
  "User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: http://localhost:7000/wp-content/sitemap.xml
Sitemap: http://localhost:7000/does-not-exist.xml")

(def sitemap-with-404
  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<?xml-stylesheet type=\"text/xsl\" href=\"http://localhost:7000/wp-sitemap-index.xsl\" ?>
<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"><sitemap><loc>http://localhost:7000/does-not-exist.xml</loc></sitemap></sitemapindex>")

(deftest test-robots-404
  (testing "robots.txt sitemap URLs that return 404s are ignored"
    (test/with-test-system [_]
      (try
        (spit "wordpress/robots.txt" robots-sitemap-404)
        (spit "wordpress/wp-content/sitemap.xml" sitemap-with-404)
        (is (zero? (:exit (test/wp-cli!
                            {:expect-warnings {#".*Got 404 for sitemap.*" 1}}
                            "wp2static" "detect"))))
        (finally
          (test/sh! {} "rm" "wordpress/robots.txt" "wordpress/wp-content/sitemap.xml"))))))

(def robots-sitemap-slashes
  "User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: http://localhost:7000//wp-sitemap.xml")

(deftest test-robots-sitemap-slashes
  (testing "robots.txt sitemap URLs with double slashes are processed"
    (test/with-test-system [_]
      (try
        (spit "wordpress/robots.txt" robots-sitemap-slashes)
        (test/wp-cli! {} "wp2static" "detect")
        (test/wp-cli! {} "wp2static" "crawl")
        (is (str/includes? (get-crawled-file "wp-sitemap-posts-post-1.xml") "http://localhost:7000/hello-world/"))
        (finally
          (test/sh! {} "rm" "wordpress/robots.txt"))))))
