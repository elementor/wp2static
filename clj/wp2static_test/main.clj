(ns wp2static-test.main
  (:require hashp.core
            [com.stuartsierra.component :as component]
            [wp2static-test.core :as core])
  (:use popen))

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
        (core/log-process! name (open-f this)))))
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
     :stop-f (fn [_] (core/sh! "mysqladmin"
                       "--socket=mariadb/data/mysql.sock"
                       "shutdown"))}))

(defn nginx []
  (shell-process
    {:name "NGINX"
     :open-f (fn [_] (popen ["nginx"
                             "-p" (System/getenv "PWD")
                             "-c" "nginx.conf"
                             "-e" "stderr"]))
     :stop-f (fn [_] (core/sh! "nginx"
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

