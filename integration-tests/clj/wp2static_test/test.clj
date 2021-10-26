(ns wp2static-test.test
  (:refer-clojure :exclude [test])
  (:require [cognitect.test-runner.api :as test-api]
            [wp2static-test.core :as core]
            [wp2static-test.main :as main]))

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
