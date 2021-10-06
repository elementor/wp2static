(ns wp2static-test.main
  (:require hashp.core
            [com.stuartsierra.component :as component]
            [wp2static-test.core :as core])
  (:use popen))

(defrecord ShellProcess [after-join-f open-f join? name process stop-f]
  component/Lifecycle
  (start [this]
    (if process
      this
      (let [process (core/log-process! name (open-f this))
            this (assoc this :process process)]
        (if-not join?
          this
          (do
            (join process)
            (after-join-f this))))))
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

(defn wordpress []
  (shell-process
    {:after-join-f (fn [wordpress]
                     (let [code (exit-code (:process wordpress))]
                       (when-not (zero? code)
                         (throw
                           (ex-info
                             (str "wordpress.sh exited with code " code)
                             {:code code}))))
                     (core/build-wp2static!)
                     wordpress)
     :name "WordPress Initializer"
     :join? true
     :open-f (fn [_] (popen ["bash" "wordpress.sh"]))}))

(defn system-map []
  (component/system-map
    :mariadb (mariadb)
    :nginx (component/using (nginx) [:wordpress])
    :php-fpm (component/using (php-fpm) [:mariadb])
    :wordpress (component/using (wordpress) [:mariadb :php-fpm])))

(defonce system (atom nil))

(defn start! []
  (swap! system #(component/start (or % (system-map)))))

(defn stop! []
  (swap! system component/stop))

(defn reload! []
  (swap! system #(do (when % (component/stop %))
                     (component/start (system-map)))))

(.addShutdownHook (Runtime/getRuntime)
  (Thread.
    (fn []
      (swap! system #(when % (component/stop %))))))
