#!/bin/sh
while :
do
    find ./provisioning/deployment_modules/ -type f |
    entr -d  sh ./provisioning/deploy_changed_file.sh /_
done
