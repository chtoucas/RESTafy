#!/bin/sh

#find . -name "*.php" -print | xargs etags -

exec exctags \
--languages=PHP \
-h ".php" -R \
--exclude="\.git" \
--exclude="\.svn" \
--exclude="Incubator" \
--totals=yes \
--tag-relative=yes \
--PHP-kinds=+cdf \
--regex-PHP='/abstract class ([^ ]*)/\1/c/' \
--regex-PHP='/interface ([^ ]*)/\1/c/' \
--regex-PHP='/(public |static |abstract |protected |private )+function ([^ (]*)/\2/f/'
