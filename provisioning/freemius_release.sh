#!/bin/bash

# run from projet root
# will overwrite static-html-output-plugin.zip in root 


rm -Rf /tmp/wordpress-static-html-plugin
mkdir /tmp/wordpress-static-html-plugin

cp -r ./{css,images,languages,library,readme.txt,views,wp-static-html-output.php,freemius} /tmp/wordpress-static-html-plugin/

cd /tmp

zip -r ./static-html-output-plugin.zip ./wordpress-static-html-plugin 

cd - 

cp /tmp/static-html-output-plugin.zip ./
