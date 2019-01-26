#!/bin/bash

# remove previous version, while preserving settings.
wp plugin deactivate --uninstall wordpress-static-html-plugin

# install latest development version
wp plugin install https://github.com/leonstafford/wp2static/archive/master.zip

# rename folder for correct plugin slug
mv wp-content/plugins/wp2static wp-content/plugins/wordpress-static-html-plugin

#activate the renamed plugin
wp plugin activate wordpress-static-html-plugin

#activate the renamed plugin
wp wp2static diagnostics

# install theme for running diagnostics
wp theme install https://github.com/leonstafford/diagnostic-theme-for-wp2static/archive/master.zip --activate

# generate an archive
wp wp2static generate

# pipe generate time into a TXT file and have this loaded by the theme via JS...

# this allows for some general benchmarking/comparison across hosts

# test deploy
wp wp2static deploy --test

# deploy (to folder "/mystaticsite/" if no existing options set)
wp wp2static deploy
