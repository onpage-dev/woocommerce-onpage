<?php
/**
 * Plugin Name: OnPage for WooCommerce
 * Plugin URI: https://onpage.it/
 * Description: Import your products from Onpage
 * Version: 1.0.32
 * Author: OnPage
 * Author URI: https://onpage.it
 * Text Domain: onpage
 * Domain Path: /i18n/languages/
 *
 * @package OnPage
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__.'/functions.php';
// require_once __DIR__.'/router.php';
require_once __DIR__.'/cli.php';


add_filter('init', function() {
  if (!current_user_can('administrator')) return;

  if (!is_dir(op_file_path('/'))) {
      mkdir(op_file_path('/'));
  }
  if (!is_dir(op_file_path('cache'))) {
      mkdir(op_file_path('cache'));
  }
  if (!is_dir(__DIR__.'/db-models')) {
      mkdir(__DIR__.'/db-models');
  }
  if (!is_dir(__DIR__.'/snapshots')) {
      mkdir(__DIR__.'/snapshots');
  }

  op_initdb();

  $api = @$_REQUEST['op-api'];
  if (!$api) return;
  try {
    switch ($api) {
      case 'save-settings':
        op_ret(op_settings(op_post('settings')));

      case 'import':
        $t1 = microtime(true);
        op_import_snapshot((bool) op_request('force_slug_regen'), (string) op_request('file_name'));
        $t2 = microtime(true);
        op_ret([
          'log' => op_record('finish'),
          'c_count' => OpLib\Term::localized()->count(),
          'p_count' => OpLib\Post::localized()->count(),
          'time' => $t2 - $t1,
        ]);

      case 'schema':
        $schema=op_getopt('schema');
        foreach ($schema->resources as $res) {
          $res->class_name = op_name_to_class($res->name);
        }
        op_ret($schema);


      case 'next-schema':
        op_ret(op_download_snapshot());

      case 'list-files':
        op_ret(op_list_files());

      case 'import-files':
        op_ret(op_import_files(op_request('files')));

      case 'list-old-files':
        op_ret(op_list_old_files());

      case 'drop-old-files':
				op_drop_old_files();
        op_ret(op_list_old_files());

      case 'upgrade':
        op_ret(op_upgrade());
      
      case 'snapshots-list':
        op_ret(op_get_snapshots_list());
      
      case 'reset-data':
        op_ret(op_reset_data());

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
    function() { require __DIR__.'/pages/import.php'; }
  );
});



// Set image for posts
add_filter( 'wp_get_attachment_image_src', function ( $image, $attachment_id, $size, $icon ){
  if (@$attachment_id[0] != '{') return $image;
  $file = @json_decode($attachment_id);
  if (!$file) return $image;
  $sizes = [
    'thumbnail' => [150, 150],
  ];
  $size = @$sizes[$size];
  if ($size) {
    $src = op_file_url($file, $size[0], $size[1], 'zoom');
  } else {
    $src = op_file_url($file);
  }
  if (!$src) return $image;
  $img = [
    $src, '', '',
  ];
  return $img;
}, 50, 4 );



// Add tab with product info
add_filter( 'woocommerce_product_data_tabs', function ( $tabs ) {
    $tabs['onpage-meta'] = array(
        'label' => __( 'OnPage Meta', 'op' ),
        'target' => 'onpage_meta',
    );
    // print_r($tabs);
    return $tabs;
} , 99 , 1 );


add_action('woocommerce_product_data_panels', function($post) {
	global $woocommerce, $post;
  $item = OpLib\Post::find($post->ID);
  require_once __DIR__.'/pages/show-meta.php';
});


add_action('product_cat_edit_form_fields', function($tag) {
  $item = OpLib\Term::find($tag->term_id);
  include __DIR__.'/pages/show-meta.php';
});
