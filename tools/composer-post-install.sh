#!/bin/sh

if hash gsed 2>/dev/null; then
  gsed -e 's#^function is_countable(#// &#' -i vendor/giacocorsiglia/wordpress-stubs/wordpress-stubs.php
else
  sed -e 's#^function is_countable(#// &#' -i vendor/giacocorsiglia/wordpress-stubs/wordpress-stubs.php
fi

