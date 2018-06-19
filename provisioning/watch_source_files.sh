#!/bin/bash


if [ -z "${INSTALL_PLUGIN_FROM_SOURCES}" ]; then 
	echo "NOT syncing plugin sources"; 
else 
	inotifywait -rm --event modify --format '%w%f' --exclude '*.swp*' --exclude '.*\.staging*' --exclude '.*\.git*' /app |
	while read filename; do
		echo 'a file has changed!'
		echo $filename
		# TODO: better here would just be to perform rsync on the one file
		. /sync_sources.sh
	done
fi

