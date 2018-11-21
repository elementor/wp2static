#!/bin/ksh

# run from project root

# give argument for name of zip to be created, ie user-leon-test-new-function

TMP_DIR=$HOME/plugintmp
mkdir -p $TMP_DIR

rm -Rf $TMP_DIR/wordpress-static-html-plugin
mkdir $TMP_DIR/wordpress-static-html-plugin

cp -r ./{css,languages,library,readme.txt,views,wp2static.php} $TMP_DIR/wordpress-static-html-plugin/

# keep free version under 1MB
#rm -Rf $TMP_DIR/wordpress-static-html-plugin/library/{S3,GitHub,CloudFront,aws,FTP}
#rm -Rf $TMP_DIR/wordpress-static-html-plugin/library/StaticHtmlOutput/{Bitbucket,BunnyCDN,FTP,GitHub,GitLab,Netlify,S3}.php

cd $TMP_DIR

zip -r -9 ./$1.zip ./wordpress-static-html-plugin 

cd -

cp $TMP_DIR/$1.zip ./
