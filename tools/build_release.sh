#!/bin/bash

######################################
##
## Build WP2Static for wp.org
##
## script archive_name dont_minify
##
## places archive in $HOME/Downloads
##
######################################

# run script from project root
EXEC_DIR=$(pwd)

TMP_DIR=$HOME/plugintmp
rm -Rf $TMP_DIR
mkdir -p $TMP_DIR

rm -Rf $TMP_DIR/static-html-output-plugin
mkdir $TMP_DIR/static-html-output-plugin


# clear dev dependencies
rm -Rf $EXEC_DIR/vendor/*
# load prod deps
composer install --no-dev


# cp all required sources to build dir
cp -r $EXEC_DIR/languages $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/src $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/vendor $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/readme.txt $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/views $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/wp2static.php $TMP_DIR/static-html-output-plugin/
cp -r $EXEC_DIR/wp2static.css $TMP_DIR/static-html-output-plugin/

cd $TMP_DIR

# tidy permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# strip comments and whitespace from each PHP file
if [ -z "$2" ]; then
  find .  ! -name 'wp2static.php' -name \*.php -exec $EXEC_DIR/tools/compress_php_file {} \;
fi

zip -r -9 ./$1.zip ./static-html-output-plugin

cd -

cp $TMP_DIR/$1.zip $HOME/Downloads/
