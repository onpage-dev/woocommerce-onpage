<?php

/**
 * Plugin Name: OnPage for WooCommerce
 * Plugin URI: https://onpage.it/
 * Description: Import your products from Onpage
 * Version: 1.1.102
 * Author: OnPage
 * Author URI: https://onpage.it
 * Text Domain: onpage
 * Domain Path: /i18n/languages/
 *
 * @package OnPage
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/functions.php';
// require_once __DIR__.'/router.php';
require_once __DIR__ . '/cli.php';


function op_version()
{
  $plugin_data = get_plugin_data(__FILE__);
  return $plugin_data['Version'];
}

add_filter('init', function () {
  $authorized = current_user_can('administrator');
  if (defined('OP_API_TOKEN') && OP_API_TOKEN && op_request('op-token') === OP_API_TOKEN) {
    $authorized = true;
  }

  if (!$authorized) return;

  if (!is_dir(op_file_path('/'))) {
    mkdir(op_file_path('/'));
  }
  if (!is_dir(op_file_path('cache'))) {
    mkdir(op_file_path('cache'));
  }
  if (!is_dir(__DIR__ . '/db-models')) {
    mkdir(__DIR__ . '/db-models');
  }
  if (!is_dir(__DIR__ . '/snapshots')) {
    mkdir(__DIR__ . '/snapshots');
  }

  op_initdb();

  $api = $_REQUEST['op-api'] ?? null;
  if (!$api) return;
  try {
    op_ignore_user_scopes(true);
    switch ($api) {
      case 'save-settings':
        op_ret(op_settings(op_post('settings')));

      case 'import':
        $t1 = microtime(true);
        op_import_snapshot(
          (bool) (op_request('force_slug_regen') ?? op_request('force-slug-regen')),
          (string) op_request('file_name'),
          (bool) op_request('force'),
          (bool) (op_request('regen-snapshot') ?? op_request('regen_snapshot'))
        );
        $t2 = microtime(true);
        op_ret([
          'log' => op_record('finish'),
          'c_count' => OpLib\Term::localized()->count(),
          'p_count' => OpLib\Post::localized()->count(),
          't_count' => OpLib\Thing::localized()->count(),
          'time' => $t2 - $t1,
        ]);

      case 'schema':
        $schema = op_stored_schema();
        if (!$schema) op_ret(null);
        foreach ($schema->resources as $res) {
          $res->class_name = op_name_to_class($res->name);
        }
        op_ret($schema);
      case 'server-config':
        op_ret([
          'product_resources' => !op_schema() ? [] : collect(op_schema()->resources)
            ->where('op_type', 'post')
            ->pluck('name'),
          'term_resources' => !op_schema() ? [] : collect(op_schema()->resources)
            ->where('op_type', 'term')
            ->pluck('name'),
          'thing_resources' => !op_schema() ? [] : collect(op_schema()->resources)
            ->where('op_type', 'thing')
            ->pluck('name'),
        ]);


      case 'next-schema':
        $filetoken = op_latest_snapshot_token();
        op_ret(op_download_snapshot($filetoken));

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

      case 'regen-slugs':
        op_ret(op_regenerate_all_slugs());

      case 'snapshots-list':
        op_ret(op_get_snapshots_list());

      case 'reset-data':
        op_ret(op_reset_data());

      default:
        op_err('Not implemented');
    }
  } catch (Exception $e) {
    op_ret([
      'error' => $e->getMessage(),
      'trace' => $e->getTrace(),
    ]);
  }
});

add_filter('admin_menu', function () {
  add_submenu_page(
    'woocommerce',
    'OnPage Importer',
    'OnPage Importer',
    'administrator',
    'onpage-importer',
    function () {
      op_ignore_user_scopes(true);
      require __DIR__ . '/pages/import.php';
    }
  );
});



// Add tab with product info
add_filter('woocommerce_product_data_tabs', function ($tabs) {
  $tabs['onpage-meta'] = array(
    'label' => __('OnPage Meta', 'op'),
    'target' => 'onpage_meta',
  );
  // print_r($tabs);
  return $tabs;
}, 99, 1);


add_action('woocommerce_product_data_panels', function ($post) {
  global $woocommerce, $post;
  $item = op_product('ID', $post->ID);
  require_once __DIR__ . '/pages/show-meta.php';
});


add_action('product_cat_edit_form_fields', function ($tag) {
  $item = op_category('term_id', $tag->term_id);
  include __DIR__ . '/pages/show-meta.php';
});


// Add the "On Page" column to the woocommerce product list
add_filter('manage_edit-product_columns', function ($columns) {
  $columns['onpage_info'] = 'On Page';
  return $columns;
});
add_filter('manage_product_posts_custom_column', function ($column, $product_id) {
  if ($column === 'onpage_info') {
    echo '#' . OpLib\PostMeta::where('post_id', $product_id)->where('meta_key', 'op_id*')->pluck('meta_value')->first();
    // echo $product->get_catalog_visibility();
    echo "<br>";
    $res_id = OpLib\PostMeta::where('post_id', $product_id)->where('meta_key', 'op_res*')->pluck('meta_value')->first();
    if ($res_id) {
      $res = op_schema()->id_to_res[$res_id] ?? null;
      if ($res) {
        echo op_label($res);
      }
    }
    // echo $product->get_catalog_visibility();
  }
}, 10, 2);


// Add the "On Page" column to the woocommerce category list
add_filter('manage_edit-product_cat_columns', function ($columns) {
  $columns['onpage_info'] = 'On Page';
  return $columns;
});
add_filter('manage_product_cat_custom_column', function ($dep, $column, $product_id) {
  if ($column === 'onpage_info') {
    echo '#' . OpLib\TermMeta::where('term_id', $product_id)->where('meta_key', 'op_id*')->pluck('meta_value')->first();
    // echo $product->get_catalog_visibility();
    echo "<br>";
    $res_id = OpLib\TermMeta::where('term_id', $product_id)->where('meta_key', 'op_res*')->pluck('meta_value')->first();
    if ($res_id) {
      $res = op_schema()->id_to_res[$res_id] ?? null;
      if ($res) {
        echo op_label($res);
      }
    }
    // echo $product->get_catalog_visibility();
  }
}, 10, 3);


// This filter corrects the image url for the data imported from On Page
add_filter('wp_get_attachment_image_src', function ($image, $attachment_id, $size, $icon) {
  static $sizes = null;

  // $image[0] = 'http://newimagesrc.com/myimage.jpg';
  if ($image !== false || strpos($attachment_id, '{') !== 0) {
    return $image;
  }

  if (!$sizes) $sizes = wp_get_registered_image_subsizes();

  $w = null;
  $h = null;
  $contain = false;
  if (is_array($size)) {
    $w = @$size[0];
    $h = @$size[1];
  } elseif (is_string($size)) {
    if (!isset($sizes[$size])) return $image;
    $w = @$sizes[$size]['width'] ?: null;
    $h = @$sizes[$size]['height'] ?: null;
    $contain = !(@$sizes[$size]['crop'] ?: false);
  }

  // Round the size to something that On Page API can understand (modulo 10)
  $steps_px = 10;
  if ($w && ($w % $steps_px)) $w += $steps_px - ($w % $steps_px);
  if ($h && ($h % $steps_px)) $h += $steps_px - ($h % $steps_px);

  try {
    $op_file = json_decode($attachment_id);

    if (!is_object($op_file) || !isset($op_file->token)) return $image;

    // See this for return type:
    //  https://developer.wordpress.org/reference/functions/wp_get_attachment_image_src/
    $url = is_admin()
      ? op_file_remote_url($op_file, $w, $h, $contain)[2]
      : op_file_url($op_file, $w, $h, $contain);
    return [$url, $w, $h, true];

    // var_dump([$image, $attachment_id]);
  } catch (\Exception $e) {
    return $image;
  }
}, 10, 4);
