<?php
/**
 * Plugin Name: OnPage for WooCommerce
 * Plugin URI: https://onpage.it/
 * Description: Import your products from Onpage
 * Version: 1.0.1
 * Author: OnPage
 * Author URI: https://onpage.it
 * Text Domain: onpage
 * Domain Path: /i18n/languages/
 *
 * @package OnPage
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__.'/functions.php';


op_initdb();

add_filter('init', function() {
  if (!current_user_can('administrator')) return;

  $api = @$_REQUEST['op-api'];
  if (!$api) return;
  try {
    switch ($api) {
      case 'save-settings':
        op_ret(op_settings(op_post('settings')));

      case 'import':
        $t1 = microtime(true);
        op_import_snapshot();
        $t2 = microtime(true);
        op_ret([
          'log' => op_record('deleted old data'),
          'c_count' => OpLib\Term::count(),
          'p_count' => OpLib\Post::count(),
          'time' => $t2 - $t1,
        ]);

      case 'schema':
        op_ret(op_extract_schema());

      case 'media':
        op_ret(op_list_media());

      case 'cache-media':
        op_ret(op_api_cache_file($_REQUEST['token']));

      case 'upgrade':
        op_ret(op_upgrade());

      default: op_err('Not implemented');
    }
  } catch ( Exception $e ) {
     op_ret([
       'error' => $e->getMessage(),
       'trace' => $e->getTrace(),
     ]);
  }
});

add_filter('admin_menu', function() {
  add_submenu_page(
    'woocommerce',
    'OnPage Importer',
    'OnPage Importer',
    'administrator',
    'onpage-importer',
    function() { require __DIR__.'/pages/import.php'; },
  );
});


require_once(__DIR__.'/router.php');
