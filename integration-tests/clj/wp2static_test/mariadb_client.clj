(ns wp2static-test.mariadb-client
  (:require [com.stuartsierra.component :as component]
            [honey.sql :as sql]
            [next.jdbc :as jdbc]
            [next.jdbc.result-set :as result-set]))

(defrecord MariaDBClient [datasource opts]
  component/Lifecycle
  (start [this]
    (if datasource
      this
      (let [datasource (jdbc/get-datasource opts)]
        (assoc this :datasource datasource))))
  (stop [this]
    (if-not datasource
      this
      (assoc this :datasource nil))))

(def jdbc-opts
  {:builder-fn result-set/as-kebab-maps})

(defn mariadb-client [opts]
  (map->MariaDBClient {:opts opts}))

(defn execute! [connectable sql-map]
  (jdbc/execute! connectable (sql/format sql-map) jdbc-opts))

(defn execute-one! [connectable sql-map]
  (jdbc/execute-one! connectable (sql/format sql-map) jdbc-opts))
