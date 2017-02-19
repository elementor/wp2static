#!/bin/bash

inotifywait -rm --event modify --format '%w%f' --exclude '.*\.swp.*' /app |
while read filename; do
    echo 'a file has changed!'
    echo $filename
    . /sync_sources.sh
done
