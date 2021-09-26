set -e

rm -rf wordpress
WP_DIR=`echo $buildInputs | tr " " "\n" | grep wordpress`
cp -r $WP_DIR/share/wordpress .
chmod +w -R wordpress
