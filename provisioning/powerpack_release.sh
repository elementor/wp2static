#!/bin/ksh

# run from project root
EXEC_DIR=$(pwd)

# give argument for name of zip to be created, ie user-leon-test-new-function

TMP_DIR=$HOME/plugintmp
mkdir -p $TMP_DIR

rm -Rf $TMP_DIR/wordpress-static-html-plugin
mkdir $TMP_DIR/wordpress-static-html-plugin

cp -r $EXEC_DIR/languages $TMP_DIR/wordpress-static-html-plugin/
cp -r $EXEC_DIR/library $TMP_DIR/wordpress-static-html-plugin/
cp -r $EXEC_DIR/readme.txt $TMP_DIR/wordpress-static-html-plugin/
cp -r $EXEC_DIR/views $TMP_DIR/wordpress-static-html-plugin/
cp -r $EXEC_DIR/wp2static.php $TMP_DIR/wordpress-static-html-plugin/
cp -r $EXEC_DIR/wp2static.css $TMP_DIR/wordpress-static-html-plugin/

mkdir -p $TMP_DIR/wordpress-static-html-plugin/powerpack
cp -r $EXEC_DIR/provisioning/deployment_modules/* $TMP_DIR/wordpress-static-html-plugin/powerpack/

# TODO: keep size smaller, strip comments any other dev bloat

cd $TMP_DIR

# tidy permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

zip -r -9 ./$1_with_powerpack.zip ./wordpress-static-html-plugin

cd -

cp $TMP_DIR/$1_with_powerpack.zip $HOME/Downloads/
