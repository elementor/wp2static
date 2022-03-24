(ns wp2static-test.test
  (:refer-clojure :exclude [test])
  (:require [clojure.string :as str]
            [clojure.test :refer [is]]
            [cognitect.test-runner.api :as test-api]
            [wp2static-test.core :as core]
            [wp2static-test.main :as main]))

(defn match-regexes [line res]
  (some #(when (re-matches % line) %) res))

(defn match-warnings [expect-warnings err]
  (let [warning-lines (->> (str/split err #"\n")
                        (filter #(str/starts-with? % "Warning: ")))
        res (keys expect-warnings)]
    {:matches (frequencies (keep #(match-regexes % res) warning-lines))
     :unmatched-lines (remove #(match-regexes % res) warning-lines)}))

(defn sh! [{:keys [expect-warnings]} & args]
  (let [result (apply core/sh! args)]
    (if expect-warnings
      (let [{:keys [unmatched-lines] :as matches}
            #__ (match-warnings expect-warnings (:err result))]
        (is (= {:matches expect-warnings :unmatched-lines []}
              (if (seq unmatched-lines)
                (assoc matches :err (:err result))
                matches))))
      (is (empty? (:err result))))
    result))

(defn wp-cli! [opts & args]
  (apply sh! opts "wp" (concat args ["--path=wordpress"] (:sh-opts opts))))

(defn test [opts]
  ;; https://clojureverse.org/t/why-doesnt-my-program-exit/3754/8
  ;; This prevents clojure -X:test from hanging
  (test-api/test opts)
  (main/stop!)
  (shutdown-agents))

(defmacro with-test-system [[name] & body]
  `(let [~name (main/start!)]
     (core/clean-wp2static-cache!)
     ~@body))
