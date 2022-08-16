(ns wp2static-test.main
  (:require [clojure.java.shell :as sh]
            [com.stuartsierra.component :as component]
            [popen :refer :all]
            [wp2static-test.core :as core]))

(def pwd (System/getenv "PWD"))

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

(def plugins-dirs
  [#_"bedrock/web/app/plugins"
   "wordpress/wp-content/plugins"])

(defrecord WP2Static [plugins-dirs started?]
  component/Lifecycle
  (start [this]
    (if started?
      this
      (do
        (core/build-wp2static! plugins-dirs)
        (assoc this :started? true))))
  (stop [this]
    (if started?
      (assoc this :started? nil)
      this)))

(defn wp2static []
  (map->WP2Static {:plugins-dirs plugins-dirs}))

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
                             "-p" pwd
                             "-c" "nginx.conf"
                             "-e" "stderr"]))
     :stop-f (fn [_] (core/sh! "nginx"
                       "-p" pwd
                       "-c" "nginx.conf"
                       "-e" "stderr"
                       "-s" "stop"))}))

(defn php-fpm []
  (shell-process
    {:name "PHP_FPM"
     :open-f (fn [_] (popen ["php-fpm"
                             "-p" "."
                             "-g" (str pwd "/php/php-fpm.pid")
                             "-y" "php/php-7.4-fpm.conf"]))
     :stop-f (fn [_]
               (when-let [pid (try
                                (slurp "php/php-fpm.pid")
                                (catch Exception _))]
                 (sh/sh "kill" "-9" pid)))}))

(defn bedrock []
  (shell-process
    {:after-join-f (fn [bedrock]
                     (let [code (exit-code (:process bedrock))]
                       (when-not (zero? code)
                         (throw
                           (ex-info
                             (str "bedrock.sh exited with code " code)
                             {:code code}))))
                     bedrock)
     :name "Bedrock Initializer"
     :join? true
     :open-f (fn [_] (popen ["bash" "bedrock.sh"]))}))

(defn wordpress []
  (shell-process
    {:after-join-f (fn [wordpress]
                     (let [code (exit-code (:process wordpress))]
                       (when-not (zero? code)
                         (throw
                           (ex-info
                             (str "wordpress.sh exited with code " code)
                             {:code code}))))
                     wordpress)
     :name "WordPress Initializer"
     :join? true
     :open-f (fn [_] (popen ["bash" "wordpress.sh"]))}))

(defn system-map []
  (component/system-map
    ;:bedrock (component/using (bedrock) [:mariadb :php-fpm])
    :mariadb (mariadb)
    :nginx (component/using (nginx) [:wordpress])
    :php-fpm (component/using (php-fpm) [:mariadb])
    :wordpress (component/using (wordpress) [:mariadb :php-fpm])
    :wp2static (component/using (wp2static) [#_:bedrock :wordpress])))

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

(defn -main []
  (start!))
