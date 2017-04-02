#!/bin/bash

inotifywait -rm --event modify --format '%w%f' --exclude '.*\.swp.*' /app |
while read filename; do
    echo 'a file has changed!'
    echo $filename
    # TODO: better here would just be to perform rsync on the one file
    . /sync_sources.sh
done
