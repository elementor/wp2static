#!/bin/bash

PROJECT_ROOT=$(pwd)
SVN_ROOT=/home/leon/svnplugindir
NEW_TAG=1.2.1

# run from project root

# copy all files to svn trunk
cp -r $PROJECT_ROOT/* $SVN_ROOT/trunk/

# remove files only used in development
rm $SVN_ROOT/trunk/*.sh
rm $SVN_ROOT/trunk/*.ini
rm $SVN_ROOT/trunk/DockerfileA
rm -r $SVN_ROOT/trunk/.git
rm -r $SVN_ROOT/trunk/*.swp
rm -r $SVN_ROOT/trunk/*.swo
rm -r $SVN_ROOT/trunk/readme.md

cd $SVN_ROOT

# tell svn to add the files
svn add trunk/*

# svn commit trunk
svn ci -m "adding files for release $NEW_TAG"

# svn create tag
svn cp trunk tags/$NEW_TAG
