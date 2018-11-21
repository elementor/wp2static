#!/bin/ksh

# run from project root

# give argument for name of zip to be created, ie user-leon-test-new-function

rm -Rf /tmp/wordpress-static-html-plugin
mkdir /tmp/wordpress-static-html-plugin

cp -r ./{css,images,languages,library,readme.txt,views,wp2static.php} /tmp/wordpress-static-html-plugin/

# keep free version under 1MB
rm -Rf /tmp/wordpress-static-html-plugin/library/{S3,GitHub,CloudFront,aws,FTP}
rm -Rf /tmp/wordpress-static-html-plugin/library/StaticHtmlOutput/{Bitbucket,BunnyCDN,FTP,GitHub,GitLab,Netlify,S3}.php

cd /tmp

zip -r ./$1.zip ./wordpress-static-html-plugin 

cd - 

cp /tmp/$1.zip ./
