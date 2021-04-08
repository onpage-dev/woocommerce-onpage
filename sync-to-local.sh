set -x
set -e
rsync -rv \
--exclude="vendor/" --exclude="db-models/" --include="*/" --include="*.php" --exclude="*" \
root@plesk.onpage.it:/var/www/vhosts/wordpress-2.plesk.onpage.it/httpdocs/wp-content/plugins/woocommerce-onpage*/ \
"$(pwd)"