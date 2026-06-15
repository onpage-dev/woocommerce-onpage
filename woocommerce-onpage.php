<?php

/**
 * Plugin Name: OnPage for WooCommerce
 * Plugin URI: https://onpage.it/
 * Description: Import your products from Onpage
 * Version: 1.1.111
 * Author: OnPage
 * Author URI: https://onpage.it
 * Text Domain: onpage
 * Domain Path: /i18n/languages/
 *
 * @package OnPage
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cli.php';


function op_version()
{
  $plugin_data = get_plugin_data(__FILE__);
  return $plugin_data['Version'];
}

add_filter('init', function () {
  \WpEloquent\Eloquent\Facades\DB::statement("SET group_concat_max_len = 100000000;");

  $authorized = current_user_can('administrator');
  if (op_api_token_valid(op_request('op-token'))) {
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
        $settings = op_post('settings');
        if (isset($settings['resource_types']) && is_array($settings['resource_types'])) {
          foreach ($settings['resource_types'] as $name => $type) {
            if ($type === 'thing') {
              unset($settings['resource_types'][$name]);
              continue;
            }
            if (!in_array($type, ['post', 'term', 'thing'], true)) {
              op_err("Invalid resource type for $name: $type");
            }
          }
        }
        if (isset($settings['import_relations']) && is_array($settings['import_relations'])) {
          foreach ($settings['import_relations'] as $resource => $parent) {
            if (!is_string($resource) || $resource === '') {
              op_err('Invalid import_relations resource name');
            }
            if (op_is_fixed_parent_relation($parent)) {
              if (!op_is_valid_fixed_parent_relation($parent)) {
                op_err("Invalid fixed parent category for resource $resource");
              }
              $settings['import_relations'][$resource] = op_normalize_import_relation_parent($parent);
              continue;
            }
            if (!is_string($parent)) {
              op_err("Invalid parent for resource $resource");
            }
          }
          $types = op_resource_types_to_array($settings['resource_types'] ?? op_get_resource_types());
          $schema = op_stored_schema();
          if ($schema) {
            $settings['import_relations'] = op_sanitize_import_relations($settings['import_relations'], $types, $schema);
          }
        }
        if (isset($settings['static_terms']) && is_array($settings['static_terms'])) {
          $settings['static_terms'] = op_sanitize_static_terms($settings['static_terms']);
        }
        if (array_key_exists('disable_original_file_import', $settings)) {
          $settings['disable_original_file_import'] = (bool) $settings['disable_original_file_import'];
        }
        if (array_key_exists('enable_imported_at_meta', $settings)) {
          $settings['enable_imported_at_meta'] = (bool) $settings['enable_imported_at_meta'];
        }
        if (isset($settings['thumbnail_format'])) {
          if (!in_array($settings['thumbnail_format'], ['png', 'jpg', 'webp'], true)) {
            op_err('Invalid thumbnail format');
          }
        }
        if (isset($settings['locale_to_lang']) && is_array($settings['locale_to_lang'])) {
          if (op_wpml_enabled()) {
            $settings['locale_to_lang'] = op_sanitize_locale_to_lang($settings['locale_to_lang']);
          } else {
            unset($settings['locale_to_lang']);
          }
        }
        if (isset($settings['fallback_langs']) && is_array($settings['fallback_langs'])) {
          $settings['fallback_langs'] = op_sanitize_fallback_langs($settings['fallback_langs']);
        }
        $saved = op_settings($settings);
        op_internal_bootstrap_language_config(true);
        op_ret($saved);

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
        $type_lists = op_resource_type_name_lists();
        $static_terms = op_get_db_static_terms();
        op_ret(array_merge($type_lists, [
          'resource_types' => op_get_resource_types(),
          'default_unmapped_resource_type' => op_get_resource_type_default(),
          'resource_types_code_hooks_active' => op_resource_types_code_hooks_active(),
          'schema_resource_types_mismatch' => op_schema_resource_types_mismatch(),
          'import_relations' => op_get_import_relations(),
          'import_relations_code_hooks_active' => op_import_relations_code_hooks_active(),
          'static_terms' => $static_terms,
          'static_term_labels' => op_get_category_labels($static_terms),
          'static_terms_code_hooks_active' => op_static_terms_code_hooks_active(),
          'file_settings_constants_active' => op_file_settings_constants_active(),
          'api_token_constant_active' => op_api_token_constant_active(),
          'locale_to_lang' => op_get_db_locale_to_lang(),
          'fallback_langs' => op_get_db_fallback_langs(),
          'language_legacy_code_active' => op_language_legacy_code_active(),
          'onpage_langs' => op_schema_langs_for_settings() ?? [],
          'wpml' => op_wpml_language_ui_config(),
          'wpml_onpage_langs' => op_wpml_onpage_langs_for_settings(),
          'wpml_locales' => op_wpml_configured_locales(),
          'wordpress_locale' => op_internal_normalize_locale_key(get_locale()),
        ]));

      case 'api-token-status':
        if (!current_user_can('administrator')) op_err('Unauthorized');
        op_ret([
          'enabled' => (bool) op_get_api_token(),
          'token' => op_get_api_token(),
          'site_url' => home_url('/'),
          'api_token_constant_active' => op_api_token_constant_active(),
        ]);

      case 'generate-api-token':
        if (!current_user_can('administrator')) op_err('Unauthorized');
        if (op_get_api_token()) op_err('API token already enabled');
        $token = op_generate_api_token();
        op_setopt('api_token', $token);
        op_ret([
          'enabled' => true,
          'token' => $token,
          'site_url' => home_url('/'),
        ]);

      case 'regenerate-api-token':
        if (!current_user_can('administrator')) op_err('Unauthorized');
        if (!op_get_api_token()) op_err('No API token to regenerate');
        $token = op_generate_api_token();
        op_setopt('api_token', $token);
        op_ret([
          'enabled' => true,
          'token' => $token,
          'site_url' => home_url('/'),
        ]);

      case 'disable-api-token':
        if (!current_user_can('administrator')) op_err('Unauthorized');
        op_setopt('api_token', null);
        op_ret([
          'enabled' => false,
          'token' => null,
          'site_url' => home_url('/'),
        ]);

      case 'search-categories':
        $ids = op_request('ids');
        if ($ids) {
          op_ret(op_get_category_labels(is_array($ids) ? $ids : [$ids]));
        }
        op_ret(op_search_product_categories((string) (op_request('q') ?? '')));


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
      op_initdb();
      require __DIR__ . '/pages/import.php';
    }
  );
  add_submenu_page(
    'woocommerce',
    'OnPage Cron Import',
    'OnPage Cron Import',
    'administrator',
    'onpage-cron-import',
    function () {
      op_ignore_user_scopes(true);
      op_initdb();
      require __DIR__ . '/pages/api-token.php';
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
