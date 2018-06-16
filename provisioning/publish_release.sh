#!/bin/bash

# getting the repo locally:
# svn co https://plugins.svn.wordpress.org/your-plugin-name $HOME/svnplugindir


PROJECT_ROOT=$(pwd)
SVN_ROOT=$HOME/svnplugindir
NEW_TAG=2.5

# manual steps for a new release

# update the files referencing the plugin version number



# get list of files changes since last release use caret to ensure correct ref:

git diff --name-only HEAD..TARGET_REVISION^


ie:


languages/static-html-output-plugin.pot
library/S3/S3.php
library/StaticHtmlOutput.php
library/StaticHtmlOutput/View.php
readme.txt
views/options-page.phtml
views/system-requirements.phtml



# run from project root

# copy each file  to svn trunk
#cp -r $PROJECT_ROOT/* $SVN_ROOT/trunk/

# image assets (for WP official pages) need to go into /assets, not /trunk
#cp -r $PROJECT_ROOT/wpassets/* $SVN_ROOT/assets/
#
#cd $SVN_ROOT

# TODO: linting, spaces to tabs, etc

###
#
#
#  Manually perform these steps from now on
#  due to having completely hosed SVN repo before! 
#
#
###

## tell svn to add the files 
## TODO: needs forcing to ensure all files added
#svn add --force * --auto-props --parents --depth infinity -q
##svn add trunk/*
#
## svn commit trunk
#svn ci -m "adding files for release $NEW_TAG"
#
## svn create tag
#svn cp trunk tags/$NEW_TAG
#
## push tag up
#svn ci -m "new tag $NEW_TAG"
