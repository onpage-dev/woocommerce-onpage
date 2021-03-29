set -x
set -e
rsync -rv \
--exclude="vendor/" --exclude=".git/" --exclude="db-models/" --include="*/" --include="*.php" --exclude="*" \
"$(pwd)/" \
root@plesk.onpage.it:/var/www/vhosts/wordpress.plesk.onpage.it/httpdocs/wp-content/plugins/woocommerce-onpage/
