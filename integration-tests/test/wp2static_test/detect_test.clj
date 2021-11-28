(ns wp2static-test.detect-test
  (:require [clojure.string :as str]
            [clojure.test :refer :all]
            [wp2static-test.test :as test]))

(defn get-crawled-file [wp path]
  (slurp (str (get-in wp [:paths :uploads]) "/wp2static-crawled-site/" path)))

(defmacro with-robots-txt [wp s & body]
  `(let [path# (str (get-in ~wp [:paths :doc-root]) "/robots.txt")]
     (try
       (spit path# ~s)
       (do ~@body)
       (finally
         (test/sh! {} "rm" "-f" path#)))))

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
  (test/with-test-system [system {}]
    (doseq [wp (vals (:wordpresses system))
            :let [wp-cli! #(apply test/wp-cli! {:path (get-in wp [:paths :cli])} %&)]]
      (when (get-in wp [:features :sitemaps?])
        (test/testing [wp "robots.txt sitemap URLs that return 404s are ignored"]
          (with-robots-txt wp robots-sitemap-404
            (let [sitemap-path (str (get-in wp [:paths :wp-content]) "/sitemap.xml")]
              (try
                (spit sitemap-path sitemap-with-404)
                (is (zero? (:exit (test/wp-cli!
                                    {:expect-warnings {#".*Got 404 for sitemap.*" 1}
                                     :path (get-in wp [:paths :cli])}
                                    "wp2static" "detect"))))
                (finally
                  (test/sh! {} "rm" "-f" sitemap-path))))))))))

(def robots-sitemap-slashes
  "User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: http://localhost:7000//wp-sitemap.xml")

(deftest test-robots-sitemap-slashes
  (test/with-test-system [system {}]
    (doseq [wp (vals (:wordpresses system))
            :let [wp-cli! #(apply test/wp-cli! {:path (get-in wp [:paths :cli])} %&)]]
      (when (get-in wp [:features :sitemaps?])
        (test/testing [wp "robots.txt sitemap URLs with double slashes are processed"]
          (with-robots-txt wp robots-sitemap-slashes
            (wp-cli! "wp2static" "detect")
            (wp-cli! "wp2static" "crawl")
            (is (str/includes? (get-crawled-file wp "wp-sitemap-posts-post-1.xml")
                  (str (get-in wp [:paths :home] "hello-world/"))))))))))
