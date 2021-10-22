(ns wp2static-test.util)

(let [b16 (org.apache.commons.codec.binary.Base16. true)]
  (defn base16 [bytes]
    (String.
      (.encode b16 bytes))))

(defn md5 [s]
  (-> (java.security.MessageDigest/getInstance "MD5")
    (.digest (.getBytes s))
    base16))
