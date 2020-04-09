#!/bin/sh
rm -f woocommerce-onpage.zip
composer install
zip -9 \
--exclude '*.git*' \
--exclude './build.sh' \
--exclude './woocommerce-onpage.zip' \
--exclude './composer.*' \
--exclude './db-models/' \
--exclude './storage/' \
-r woocommerce-onpage.zip .
