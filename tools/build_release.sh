#!/bin/bash

# run from project root

EXEC_DIR=$(pwd)
# give argument for name of zip to be created, ie user-leon-test-new-function

TMP_DIR=$HOME/plugintmp
mkdir -p $TMP_DIR

rm -Rf $TMP_DIR/static-html-output-plugin
mkdir $TMP_DIR/static-html-output-plugin


cp -r $EXEC_DIR/languages $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/powerpack $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/library $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/readme.txt $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/views $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/wp2static.php $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/wp2static.css $TMP_DIR/static-html-output-plugin/

cd $TMP_DIR

rm static-html-output-plugin/library/.htaccess

# tidy permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# strip comments and whitespace from each PHP file
# takes size from 990K to 733K
find .  ! -name 'wp2static.php' -name \*.php -exec $EXEC_DIR/provisioning/compress_php_file {} \;

zip -r -9 ./$1.zip ./static-html-output-plugin

cd -

cp $TMP_DIR/$1.zip $HOME/Downloads/
