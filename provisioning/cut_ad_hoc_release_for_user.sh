#!/bin/bash

# run from projet root

# give argument for name of zip to be created, ie user-leon-test-new-function

rm -Rf /tmp/wordpress-static-html-plugin
mkdir /tmp/wordpress-static-html-plugin

cp -r ./{css,images,languages,library,readme.txt,views,wp-static-html-output.php} /tmp/wordpress-static-html-plugin/

cd /tmp

zip -r ./$1.zip ./wordpress-static-html-plugin 

cd - 

cp /tmp/$1.zip ./
