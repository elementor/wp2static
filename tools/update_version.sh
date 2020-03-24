#!/bin/sh

OLD_VERSION="$1"
NEW_VERSION="$2"

git grep -l "$OLD_VERSION" | xargs sed -i "s/$OLD_VERSION/$NEW_VERSION/g"

echo "Updated from $OLD_VERSION to $NEW_VERSION"
