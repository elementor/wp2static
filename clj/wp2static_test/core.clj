(ns wp2static-test.core
  (:require [clojure.java.shell :as sh]
            [clojure.tools.logging.readable :as log])
  (:use popen))

(defn sh! [& args]
  (let [{:keys [exit] :as result} (apply sh/sh args)]
    (if (zero? exit)
      result
      (throw (ex-info (str "Subprocess returned with exit code " exit)
               {:args args
                :result result})))))

(defn wp-cli! [& args]
  (apply sh! "wp" (concat args ["--path=wordpress"])))

(defn build-wp2static! []
  (let [fname (str "wp2static-" (System/currentTimeMillis))
        zip-name (str fname ".zip")
        plugins-dir "wordpress/wp-content/plugins"]
    (try
      (sh! "bash" "./tools/build_release.sh" fname
        :dir (str (System/getenv "PWD") "/../wp2static"))
      (sh! "mv" (str (System/getenv "HOME") "/Downloads/" zip-name) "."
        :dir plugins-dir)
      (sh! "rm" "-rf" "wp2static" :dir plugins-dir)
      (sh! "unzip" zip-name :dir plugins-dir)
      (sh! "rm" zip-name :dir plugins-dir)
      (wp-cli! "plugin" "activate" "wp2static")
      (finally
        (sh/sh "rm" zip-name :dir plugins-dir)))))

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
