#!/bin/bash
while inotifywait -qrm --event modify --format '%w%f' /app; do
    echo 'something has changed!'
    . /sync_sources.sh
done
