(ns wp2static-test.main
  (:require hashp.core
            [clojure.java.shell :as sh]
            [clojure.tools.logging.readable :as log]
            [com.stuartsierra.component :as component])
  (:use popen))

(defn sh! [& args]
  (let [{:keys [exit] :as result} (apply sh/sh args)]
    (if (zero? exit)
      result
      (throw (ex-info (str "Subprocess returned with exit code " exit)
               {:args args
                :result result})))))

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
      (sh! "wp" "plugin" "activate" "wp2static" "--path=wordpress")
      (finally
        (sh/sh "rm" zip-name :dir plugins-dir)))))

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

(defn start-mariadb! []
  (popen ["bash" "mariadb.sh"]))

(defn start-nginx! []
  (popen ["bash" "nginx.sh"]))

(defn start-php-fpm! []
  (popen ["bash" "php-fpm.sh"]))

(defrecord ShellProcess [open-f name process stop-f]
  component/Lifecycle
  (start [this]
    (if process
      this
      (assoc this :process
        (log-process! name (open-f this)))))
  (stop [this]
    (if-not process
      this
      (do
        ((or stop-f kill) process)
        (assoc this :process nil)))))

(defn shell-process [m]
  (map->ShellProcess m))

(defn mariadb []
  (shell-process
    {:name "MariaDB"
     :open-f (fn [_] (popen ["bash" "mariadb.sh"]))
     :stop-f (fn [_] (sh! "mysqladmin"
                       "--socket=mariadb/data/mysql.sock"
                       "shutdown"))}))

(defn nginx []
  (shell-process
    {:name "NGINX"
     :open-f (fn [_] (popen ["nginx"
                             "-p" (System/getenv "PWD")
                             "-c" "nginx.conf"
                             "-e" "stderr"]))
     :stop-f (fn [_] (sh! "nginx"
                       "-p" (System/getenv "PWD")
                       "-c" "nginx.conf"
                       "-e" "stderr"
                       "-s" "stop"))}))

(defn php-fpm []
  (shell-process
    {:name "PHP_FPM"
     :open-f (fn [_] (popen ["php-fpm" "-c" "php" "-y" "php/php-7.4-fpm.conf"]))}))

(defn system-map []
  (component/system-map
    :mariadb (mariadb)
    :nginx (component/using (nginx) [:wordpress])
    :php-fpm (component/using (php-fpm) [:mariadb])
    :wordpress (component/using
                 (shell-process
                   {:name "WordPress Initializer"
                    :open-f (fn [_] (popen ["bash" "wordpress.sh"]))})
                 [:mariadb :php-fpm])))

(defonce system (atom nil))

(defn start! []
  (swap! system #(component/start (or % (system-map)))))

(defn stop! []
  (swap! system component/stop))

(defn reload! []
  (swap! system #(do (when % (component/stop %))
                     (component/start (system-map)))))

