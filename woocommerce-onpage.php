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
        op_import_snapshot((bool) op_post('force_slug_regen'));
        $t2 = microtime(true);
        op_ret([
          'log' => op_record('deleted old data'),
          'c_count' => OpLib\Term::count(),
          'p_count' => OpLib\Post::count(),
          'time' => $t2 - $t1,
        ]);

      case 'schema':
        op_ret(op_getopt('schema'));

      case 'next-schema':
        op_ret(op_extract_schema());

      case 'list-file':
        op_ret(op_list_files());

      case 'import-files':
        op_ret(op_import_files(op_request('files')));

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



// Set image for posts
add_filter( 'wp_get_attachment_image_src', 'pn_change_product_image_link', 50, 4 );
function pn_change_product_image_link( $image, $attachment_id, $size, $icon ){
  if (@$attachment_id[0] != '{') return $image;
  $file = json_decode($attachment_id);
  $sizes = [
    'thumbnail' => [150, 150],
  ];
  $size = @$sizes[$size];
  if ($size) {
    $src = op_file_url($file, $size[0], $size[1], 'zoom');
  } else {
    $src = op_file_url($file);
  }
  $img = [
    $src, '', '',
  ];
  return $img;
}


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
  exit;
});




require_once(__DIR__.'/router.php');
