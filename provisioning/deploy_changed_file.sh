#!/bin/sh

mkdir -p ./powerpack

cp ./provisioning/deployment_modules/* ./powerpack/

ls -tp ./provisioning/deployment_modules/ | grep -v /$ | head -1


