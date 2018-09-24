#!/bin/bash
#
# temporary maintenance script
#
FILE_TARGET="$( cat todo.list | head -n1 )"
echo "" >> "$FILE_TARGET"
git add .
git commit -a
echo "$FILE_TARGET" >> todo-done.list
tail -n +2 todo.list > todo.list.tmp
mv todo.list.tmp todo.list