#!/bin/ksh

# run from project root

# give argument for name of zip to be created, ie user-leon-test-new-function

TMP_DIR=$HOME/plugintmp
mkdir -p $TMP_DIR

rm -Rf $TMP_DIR/wordpress-static-html-plugin
mkdir $TMP_DIR/wordpress-static-html-plugin

cp -r ./{css,languages,library,readme.txt,views,wp2static.php} $TMP_DIR/wordpress-static-html-plugin/

cp -r ./powerpack $TMP_DIR/wordpress-static-html-plugin/

# TODO: keep size smaller, strip comments any other dev bloat

cd $TMP_DIR

zip -r -9 ./$1_with_powerpack.zip ./wordpress-static-html-plugin

cd -

cp $TMP_DIR/$1_with_powerpack.zip ../
