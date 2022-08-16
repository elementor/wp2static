(ns wp2static-test.core
  (:require [clojure.java.shell :as sh]
            [clojure.string :as str]
            [clojure.tools.logging.readable :as log]
            [popen :refer :all]))

(defn sh! [& args]
  (let [{:keys [exit] :as result} (apply sh/sh args)]
    (if (zero? exit)
      result
      (throw (ex-info (str "Subprocess returned with exit code " exit)
               {:args args
                :result result})))))

(defn bedrock-cli! [& args]
  (apply sh! "wp" (concat args ["--path=bedrock/web/wp"])))

(defn wp-cli! [& args]
  (apply sh! "wp" (concat args ["--path=wordpress"])))

(defn build-wp2static! [plugins-dirs]
  (let [fname (str "wp2static-" (System/currentTimeMillis))
        zip-name (str fname ".zip")
        wp2static-path (->> (or (System/getenv "WP2STATIC_PATH")
                              (str (System/getenv "PWD") "/.."))
                         (sh! "readlink" "-f")
                         :out
                         str/trim)]
    (if (System/getenv "WP2STATIC_SYMLINK")
      (do
        (sh! "composer" "install" "--no-dev" "--optimize-autoloader"
          :dir wp2static-path)
        (doseq [plugins-dir plugins-dirs]
          (sh! "ln" "-s" wp2static-path :dir plugins-dir)))
      (try
        (sh! "bash" "./tools/build_release.sh" fname :dir wp2static-path)
        (doseq [plugins-dir plugins-dirs]
          (try
            (sh! "cp" (str (System/getenv "HOME") "/Downloads/" zip-name) "."
              :dir plugins-dir)
            (sh! "rm" "-rf" "wp2static" :dir plugins-dir)
            (sh! "unzip" zip-name :dir plugins-dir)
            (finally
              (sh! "rm" "-f" zip-name :dir plugins-dir))))
        (finally
          (sh! "rm" "-f" zip-name :dir (str (System/getenv "HOME") "/Downloads/")))))
    ;(bedrock-cli! "plugin" "activate" "wp2static")
    (wp-cli! "plugin" "activate" "wp2static")))

(defn clean-wp2static-cache! []
  (wp-cli! "wp2static" "delete_all_cache" "--force"))

(defn log-exit-code! [name process]
  (log/info name "exited with code" (exit-code process)))

(defn log-stderr! [name process]
  (with-open [reader (stderr process)]
    (doseq [line (line-seq reader)]
      (log/error name line))))

(defn log-stdout! [process]
  (with-open [reader (stdout process)]
    (doseq [line (line-seq reader)]
      (log/info name line))))

(defn log-process! [name process]
  (future (log-stderr! (str "[" name "]") process))
  (future (log-stdout! (str "[" name "]") process))
  (future (log-exit-code! name process))
  process)
