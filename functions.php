<?php
if (!defined( 'ABSPATH' )) exit;

// error_reporting(E_ALL ^ E_NOTICE);
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once __DIR__.'/vendor/autoload.php';
// use Illuminate\Database\Capsule\Manager as DB;
use \WeDevs\ORM\Eloquent\Facades\DB;


global $wpdb;
define('OP_PLUGIN', true);
define('OP_WP_PREFIX', $wpdb->prefix);

function op_debug() {
  error_reporting(E_ALL ^ E_NOTICE);
  ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

function op_download_json($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  $result = curl_exec($ch);
  $err = curl_errno($ch);
  if ($err) {
      throw new \Exception("Cannot download file, curl error [$err]");
  }
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return $status == 200 && $result ? json_decode($result) : null;
}

function op_initdb() {
  // print_r(DB::table('options')
  // ->where('option_name', 'like', 'op\\_ions%')->get());
  // exit;

  if (!@op_settings()->migration) {
    $query = DB::table('options')
      ->where('option_name', 'like', 'op\\_%')
      ->where('option_name', 'not like', 'op\\_ions%');
    $new_opts = $query->get()->map(function($opt) {
      return [
        'option_name' => 'on-page-'.substr($opt->option_name, 3),
        'option_value' => $opt->option_value,
        'autoload' => $opt->autoload,
      ];
    })->toArray();
    DB::table('options')->insert($new_opts);
    op_settings(null, true);
    $query->delete();
  }

  // Create helper columns
  if (op_settings()->migration < 33) {
    $orig_mode = DB::select('SELECT @@sql_mode as mode')[0]->mode;
    DB::statement("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    @DB::statement("ALTER TABLE `".OP_WP_PREFIX."posts` ADD COLUMN `op_res` bigint unsigned NULL;");
    @DB::statement("ALTER TABLE `".OP_WP_PREFIX."posts` ADD COLUMN `op_id` bigint unsigned NULL;");


    @DB::statement("ALTER TABLE `".OP_WP_PREFIX."terms` ADD COLUMN `op_res` bigint unsigned NULL;");
    @DB::statement("ALTER TABLE `".OP_WP_PREFIX."terms` ADD COLUMN `op_id` bigint unsigned NULL;");

    @DB::statement("ALTER TABLE `".OP_WP_PREFIX."posts`
                    ADD UNIQUE `op_res_op_id` (`op_res`, `op_id`),
                    ADD INDEX `op_res` (`op_res`),
                    ADD UNIQUE `op_id` (`op_id`)");
    @DB::statement("ALTER TABLE `".OP_WP_PREFIX."terms`
                    ADD UNIQUE `op_res_op_id` (`op_res`, `op_id`),
                    ADD INDEX `op_res` (`op_res`),
                    ADD UNIQUE `op_id` (`op_id`)");

    @DB::statement("ALTER TABLE `".OP_WP_PREFIX."posts` ADD COLUMN `op_dirty` BOOL;");
    @DB::statement("ALTER TABLE `".OP_WP_PREFIX."terms` ADD COLUMN `op_dirty` BOOL;");

    op_setopt('migration', 33);
    DB::statement("SET sql_mode = '$orig_mode'");
  }

  if (op_settings()->migration < 50) {
    $orig_mode = DB::select('SELECT @@sql_mode as mode')[0]->mode;
    DB::statement("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    try {
      @DB::statement("ALTER TABLE `".OP_WP_PREFIX."terms` ADD COLUMN `op_order` FLOAT NULL;");
    } catch (\Exception $e) { }
    try {
      @DB::statement("ALTER TABLE `".OP_WP_PREFIX."terms` ADD INDEX `op_order` (`op_order`)");
    } catch (\Exception $e) { }



    op_setopt('migration', 50);
    DB::statement("SET sql_mode = '$orig_mode'");
  }

  if (op_settings()->migration < 51) {
    op_debug();
    foreach (\OpLib\Post::all() as $item) {
      if (!$item->getMeta('op_lang*')) {
          $item->meta()->create([
              'meta_key'   => 'op_lang*',
              'meta_value' => op_wpml_default() ?: op_locale() ?: 'it',
          ]);
      }
      if (!$item->getMeta('op_res*')) {
        $item->meta()->create([
          'meta_key' => 'op_res*',
          'meta_value' => $item->op_res,
          ]);
      }
      if (!$item->getMeta('op_id*')) {
        $item->meta()->create([
          'meta_key' => 'op_id*',
          'meta_value' => $item->op_id,
        ]);
      }
    }
    foreach (\OpLib\Term::all() as $item) {
      if (!$item->getMeta('op_lang*')) {
        $item->meta()->create([
          'meta_key' => 'op_lang*',
          'meta_value' => op_wpml_default() ?: op_locale() ?: 'it',
        ]);
      }
      if (!$item->getMeta('op_res*')) {
        $item->meta()->create([
          'meta_key' => 'op_res*',
          'meta_value' => $item->op_res,
          ]);
      }
      if (!$item->getMeta('op_id*')) {
        $item->meta()->create([
          'meta_key' => 'op_id*',
          'meta_value' => $item->op_id,
        ]);
      }
    }
    op_setopt('migration', 51);
  }

}

function op_setopt($opt, $value) {
  $settings = op_settings();
  $settings->$opt = $value;
  op_settings($settings);
}

function op_getopt($opt, $default = null) {
  return isset(op_settings()->$opt) ? op_settings()->$opt : $default;
}

function op_post($field) {
  static $req_json;
  if (!$req_json) {
    $req_json = json_decode(file_get_contents('php://input'), true);
  }
  return @$req_json[$field];
}

function op_settings($settings = null, $flush_cache = false) {
  static $cached_settings = null;
  if ($flush_cache) $cached_settings = null;
  if (!empty($settings)) {
    $opts = [];
    foreach ((array) $settings as $key => $value) {
      update_option("on-page-$key", json_encode($value));
    }
    

  } elseif ($cached_settings) {
    return $cached_settings;
  }

  $ret = (object) [];
  $opts = DB::table('options')->where('option_name', 'like', 'on-page-%')->pluck('option_value', 'option_name')->all();
  foreach ($opts as $key => $value) {
    $ret->{substr($key, 8)} = json_decode($value);
  }
  $cached_settings = $ret;
  return $ret;
}

function op_download_snapshot(object $sett = null) {
  if (!$sett) $sett = op_settings();

  // op_record('start import');
  $info = op_download_json("https://{$sett->company}.onpage.it/api/view/{$sett->token}/dist") or op_ret(['error' => 'Cannot access API - check your settings']);
  if (!@$info->token) {
    op_ret(['error' => 'No snapshot present, generate it on OnPage']);
  }
  $db = op_download_json("https://{$sett->company}.onpage.it/api/storage/{$info->token}") or op_ret(['error' => 'Cannot download snapshot']);
  // op_record('download completed');
  return $db;
}

function op_save_snapshot_file($db){
  $name = date('Y-m-d H:i:s')."-snapshot.json";
  file_put_contents(op_dir("snapshots/$name"), json_encode($db));
}

function op_dir($dir) {
  return __DIR__."/$dir";
}

function op_del_old_snapshots() {
  foreach (array_slice(op_get_snapshots_list(), 3) as $i => $name) {
    @unlink(op_dir("snapshots/$name"));
  }
}

function op_get_snapshots_list(){
  return array_reverse(array_map('basename', glob(op_dir("/snapshots/*.json")))); 
}



function op_get_saved_snapshot($file_name){
  $path= op_dir("snapshots/$file_name");
  if(is_file($path)){
    return json_decode(file_get_contents($path));
  }
}


function op_schema(object $set = null) {
  static $schema = null;
  if ($set) {
    $set = clone $set;
    foreach ($set->resources as $res_i => $res) {
      $clean_res = clone $res;
      unset($clean_res->data);
      $set->resources[$res_i] = $clean_res;
    }
    op_setopt('schema', $set);
    $schema = null;
  }
  if (!$schema) {
    $schema = op_getopt('schema');
    $schema->id_to_res = [];
    $schema->name_to_res = [];
    foreach ($schema->resources as &$res) {
      $schema->id_to_res[$res->id] = $res;
      $schema->name_to_res[$res->name] = $res;
      foreach ($res->fields as &$field) {
        $schema->id_to_field[$field->id] = $field;
      }
    }
    foreach ($schema->resources as $res_i => &$res) {
      $res->id_to_field = [];
      $res->name_to_field = [];
      foreach ($res->fields as &$field) {
        $res->id_to_field[$field->id] = $field;
        $res->name_to_field[$field->name] = $field;

        $field->res = $res;
        if ($field->type == 'relation') {
          $field->rel_res = $schema->id_to_res[$field->rel_res_id];
          $field->rel_field = $schema->id_to_field[$field->rel_field_id];
        }
      }
    }
  }
  return $schema;
}

function op_err($msg, $data = []) {
  $data = (array) $data;
  $data['error'] = $msg;
  op_ret($data);
}

function op_ret($data) {
  if (is_object($data)) $data = (array) $data;
  if (@$data['error']) {
    http_response_code(400);
  }

  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::line(print_r($data));
    exit;
  }
  header("Content-Type: application/json");
  echo json_encode($data);
  exit;
}

function op_record($label, $end = false) {
  static $tRecordStart;
  static $tStartQ;
  static $steps;
  if (!$tRecordStart) $tRecordStart = microtime(true);
  if (!$tStartQ) $tStartQ = microtime(true);
  $tS = microtime(true);
  $tElapsedSecs = $tS - $tRecordStart;
  $tElapsedSecsQ = $tS - $tStartQ;
  $ram = str_pad(number_format(memory_get_usage(true)/1024/1024, 1), 6, " ", STR_PAD_LEFT);
  $sElapsedSecs = str_pad(number_format($tElapsedSecs, 3), 8, " ", STR_PAD_LEFT);
  $sElapsedSecsQ = str_pad(number_format($tElapsedSecsQ, 3), 8, " ", STR_PAD_LEFT);
  $tStartQ = $tS;
  $message = "$sElapsedSecs $sElapsedSecsQ  {$ram}MB $label";
  $steps[] = $message;
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::line($message);
  }
  file_put_contents(__DIR__."/storage/.log", "$message\n", FILE_APPEND);
  return $steps;
}

function op_cache(string $id, $callback) {
  static $cached = [];
  if (!isset($cached[$id])) {
    $cached[$id] = $callback();
  }
  return $cached[$id];
}

function op_page($name = null, $file = null) {
  static $pages = [];
  if ($file) {
    $pages[$name] = $file;
  }
  return $pages;
}

function op_e($string) {
  if (is_array($string)) {
    return array_map('op_e', $string);
  } else {
    return htmlentities($string, ENT_QUOTES);
  }
}

function op_snake_to_camel($str) {
  $str = explode('_', $str);
  $ret = '';
  foreach ($str as $s) {
    $ret.= strtoupper(@$s[0]).substr($s, 1);
  }
  return $ret;
}

function op_wpml_enabled() {
  return !!op_wpml_default();
}

function op_wpml_default() {
  static $ret = -1;
  if ($ret === -1) {
    $ret = apply_filters('wpml_default_language', NULL);
  }
  return $ret;
}
function op_wpml_langs() :? array {
  if (!op_wpml_enabled()) return null;
  $icl_deflang = op_wpml_default();
  $wpml_langs = [];
  foreach (apply_filters( 'wpml_active_languages', null) as $lang => $_) {
    if ($lang != $icl_deflang) {
      $wpml_langs[] = $lang;
    }
  }
  return $wpml_langs;
}

function op_langs() {
  $langs = [ op_wpml_default() ?: op_locale()?: 'it' ];
  if ($other_langs = op_wpml_langs()) {
    $langs = array_merge($langs, $other_langs);
  }
  return $langs;
}

function op_slug(string $title, $base_class, string $old_slug = null) {
  $slug = sanitize_title_with_dashes($title);

  $suffix = '';
  while ($old_slug != $slug.$suffix && $base_class->slugExists($slug.$suffix)) {
    if (!$suffix) {
      $suffix = 2;
    } else {
      $suffix++;
    }
  }
  return $slug.$suffix;
}

function op_import_snapshot(bool $force_slug_regen = false, string $file_name=null) {
  ini_set('memory_limit','2G');
  set_time_limit(600);
  ini_set('max_execution_time', '600');

  if(!$file_name){
    $schema_json = op_download_snapshot();
    $snapshot_to_save = $schema_json;
    op_record('download completed');
  } else {
    $schema_json = op_get_saved_snapshot($file_name);
  }

  // Create imported_at field
  $schema_json->imported_at = date('Y-m-d H:i:s');

  // Overwrite which resource must be imported as a product
  $overwrite_products = apply_filters('on_page_product_resources', null);
  if (is_array($overwrite_products)) {
    foreach ($schema_json->resources as $res) {
      $res->is_product = in_array($res->name, $overwrite_products);
    }
  }

  // Store the new schema (this will remove the data from the schema)
  $schema = op_schema($schema_json);


  
  $all_items = []; // [res][id][lang] -> wpid
  $new_items = []; // [res][id][lang] -> wpid
  $imported_at = date('Y-m-d H:i:s');
  
  $langs = op_langs();
  foreach ($schema->resources as $res_i => $res) {
    $data = $schema_json->resources[$res_i]->data;
    op_record("Importing $res->label (".count($data)." items)...");
    op_import_resource($schema, $res, $data, $langs, $imported_at, $all_items, $new_items); 
    op_record("completed $res->label");
  }
  op_record("Importing relations...");
  op_import_snapshot_relations($schema, $schema_json, $all_items);
  op_record("done");

  // Delete old data
  op_record('Deleting outdated products and categories...');
  op_reset_data(function($q) use ($imported_at) {
    $q->isOutdated($imported_at);
  }, function($q) use ($imported_at) {
    $q->isOutdated($imported_at);
  });
  op_record('done');


  op_record('Creating php models');
  foreach ($schema->resources as $res) op_gen_model($schema, $res);
  op_record('done');
  
  op_record('Importing relations...');
  op_link_imported_data($schema);
  op_record('done');
  
  op_record('Generating slugs...');
  op_regenerate_items_slug($force_slug_regen ? $all_items : $new_items);
  op_record('done');
  
  flush_rewrite_rules();
  op_record('permalinks flushed');
  
  if (isset($snapshot_to_save)) {
    op_record('Storing snapshot...');
    op_save_snapshot_file($snapshot_to_save);
    op_del_old_snapshots();
    op_record('done');
  }

  do_action('op_import_completed');
}

function op_link_imported_data($schema) {
  $relations = apply_filters('op_import_relations', null);
  if (empty($relations)) return;
  

  foreach ($relations as $resource_name => $parent_relation) {
    $res = collect($schema->resources)->firstWhere('name', $resource_name);
    if (!$res) op_err("Cannot find resource $resource_name for hook op_import_relations; available resources: ".collect($schema->resources)->pluck('name')->implode(', '));

    $rel_field = collect($res->fields)->where('type', 'relation')->firstWhere('name', $parent_relation);
    if (!$rel_field) op_err("Cannot find relation $parent_relation for hook op_import_relations");

    $class = op_name_to_class($res->name);
    if (op_wpml_enabled()) {
      op_locale(op_wpml_default());
      do_action( 'wpml_switch_language', op_wpml_default() );
    }
    $terms = $class::with($parent_relation)->get();
  
    foreach ($terms as $child_term) {
      foreach ($child_term->$parent_relation as $parent_term) {
        $ret = wp_update_term($child_term->id, 'product_cat', [
          'parent' => $parent_term->id,
          'slug' => $child_term->slug,
        ]);
        if ($ret instanceof \WP_Error) {
          op_err("Error while setting parent for a relation", ['wp_err' => $ret]);
        }
      }
    }
  }

  if (op_wpml_enabled()) {
    $sync_helper = wpml_get_hierarchy_sync_helper('post');
    $sync_helper->sync_element_hierarchy( 'product' );
    $sync_helper = wpml_get_hierarchy_sync_helper('term');
    $sync_helper->sync_element_hierarchy( 'product_cat' );
  }

  // Reset category count
  delete_option("product_cat_children");
}


function op_import_resource(object $db, object $res, array $res_data, array $langs, string $imported_at, array &$all_items, array &$new_items) {
  $lab = collect($res->fields)->whereNotIn('type', ['relation', 'file', 'image'])->first();
  $lab_img = collect($res->fields)->where('type', 'image')->first();
  $base_table = $res->is_product ? 'posts' : 'terms';
  $base_table_key = $res->is_product ? 'ID' : 'term_id';
  $base_table_slug = $res->is_product ? 'post_name' : 'slug';
  $base_tablemeta = $res->is_product ? 'postmeta' : 'termmeta';
  $base_tablemeta_ref = $res->is_product ? 'post_id' : 'term_id';
  $base_class = $res->is_product ? \OpLib\Post::class : \OpLib\Term::class;
  $icl_type = $res->is_product ? 'post_product' : 'tax_product_cat';
  $icl_deflang = op_wpml_default();

  // Create map of resource fields [id => field]
  $field_map = [];
  foreach ($res->fields as $f) $field_map[$f->id] = $f;
  // op_record('mapped $field_map');

  // Start inserting
  $object_ids = [];

  $icl_translations = [];
  $all_meta = [];
  $all_icl_object_ids = [];
  $current_objects = [];
  $base_class::$only_reserverd = true;
  foreach ($base_class::whereRes($res->id)->get() as $x) {
    $current_objects["{$x->getId()}-{$x->getLang()}"] = $x;
  }
  $base_class::$only_reserverd = false;
  // op_record('mapped $current_objects');

  $icl_trid = DB::table('icl_translations')->max('trid') + 2;

  foreach ($res_data as $thing_i => $thing) {
    if ($thing_i && $thing_i%100 == 0) {
      op_record("- $thing_i/".count($res_data));
    }
    $icl_primary_id = null;
    $icl_trid++;
    
    // Create the item in each language - first language is the primary one
    foreach ($langs as $lang) {
      // op_record("- lang $lang");
      $is_primary = !$icl_primary_id;
      $lab_img_field = $lab_img ? $lab_img->id.($lab_img->is_translatable ? "_{$db->langs[0]}" : '') : null;
      $lab_field = $lab ? $lab->id.($lab->is_translatable ? "_{$lang}" : '') : null;

      $label = @$thing->fields->$lab_field;
      if (is_null($label)) $label = 'unnamed';


      // Look for the object if it exists already
      $object = @$current_objects["{$thing->id}-{$lang}"];

      
      // Prepare data
      $data = !$res->is_product ? [
        'name' => $label,
        'slug' => $object
          ? $object->slug
          : sanitize_title_with_dashes("{$thing->id}-$label-$lang"),
        'term_group' => 0,
        'op_order' => $thing_i,
      ] : [
        'post_author' => 1,
        'post_date' => @$thing->created_at ?: date('Y-m-d H:i:s'),
        'post_date_gmt' => @$thing->created_at ?: date('Y-m-d H:i:s'),
        'post_content' => '',
        'post_title' => $label,
        'post_excerpt' => '',
        'post_status' => 'publish',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_password' => '',
        'post_name' => $object
          ? $object->post_name
          : sanitize_title_with_dashes("{$thing->id}-$label-$lang"),
        'to_ping' => '',
        'pinged' => '',
        'post_modified' => $imported_at,
        'post_modified_gmt' => $imported_at,
        'post_content_filtered' => '',
        'post_parent' => 0,
        'guid' => '',
        'menu_order' => $thing_i,
        'post_type' => 'product',
        'post_mime_type' => '',
        'comment_count' => 0,
      ];
      // op_record("- ready to upsert");
      // Create or update the object
      if ($object) {
        $object_id = $object->$base_table_key;
        DB::table($base_table)->where($base_table_key, $object_id)->update($data);
        // op_record("- updated");
      } else {
        $object_id = DB::table($base_table)->insertGetId($data);
        if (!$object_id) {
          op_err("Cannot insert into $base_table: ".DB::instance()->db->last_error, [
            'data' => $data,
          ]);
        }
        $new_items[$res->id][$thing->id][$lang] = $object_id;
        // op_record("- created");
      }
      $object_ids["{$thing->id}-$lang"] = $object_id;
      $all_items[$res->id][$thing->id][$lang] = $object_id;

      $tax_id = null;
      if (!$res->is_product) {
        $tax_id = @DB::table('term_taxonomy')->where('term_id', $object_id)->first()->term_taxonomy_id;
        if (!$tax_id) {
          $tax_id = DB::table('term_taxonomy')->insertGetId([
            'term_id' => $object_id,
            'taxonomy' => 'product_cat',
            'parent' => 0,
            'count' => 1,
          ]);
        }
        // op_record("- updated tax");
      }

      // Delete all relations with parents
      if ($res->is_product) {
        DB::table('term_relationships')->where('object_id', $object_id)->delete();
        wp_set_object_terms($object_id, 'simple', 'product_type');
        // op_record("- wp_set_object_terms");
      }

      // Calculate base meta
      if (true) {
        $base_meta = [];
        $base_meta[] = [
          $base_tablemeta_ref => $object_id,
          'meta_key' => 'op_id*',
          'meta_value' => $thing->id,
        ];
        $base_meta[] = [
          $base_tablemeta_ref => $object_id,
          'meta_key' => 'op_res*',
          'meta_value' => $res->id,
        ];
        $base_meta[] = [
          $base_tablemeta_ref => $object_id,
          'meta_key' => 'op_lang*',
          'meta_value' => $lang,
        ];
        $base_meta[] = [
          $base_tablemeta_ref => $object_id,
          'meta_key' => 'op_imported_at*',
          'meta_value' => $imported_at,
        ];

        if ($lab_img_field && @$thing->fields->$lab_img_field) {
          $base_meta[] = [
            $base_tablemeta_ref => $object_id,
            'meta_key' => $res->is_product ? '_thumbnail_id' : 'thumbnail_id',
            'meta_value' => json_encode(
              $thing->fields->$lab_img_field
            ),
          ];
        }
        // op_record("- merging meta");
        $all_meta = array_merge($all_meta, $base_meta);
        // op_record("- merged: ".count($all_meta));
      }
      
      // If this is the primary language
      $icl_object_id = $res->is_product ? $object_id : $tax_id;
      $all_icl_object_ids[] = $icl_object_id;
      if (!$icl_object_id) {
        op_err("cannot get taxonomy id: $object_id-$tax_id");
      }
      $all_meta = array_merge($all_meta, op_generate_data_meta($res, $thing, $object_id, $field_map, $base_tablemeta_ref));
      if ($is_primary) {
        // Mark item as primary - others are translations
        $icl_primary_id = $object_id;
  
        // Recreate the metadata

        // Create icl translation
        $icl_translations[] = [
          'element_id' => $icl_object_id,
          'element_type' => $icl_type,
          'language_code' => $lang,
          'trid' => $icl_trid,
          'source_language_code' => null,
        ];
      } else {
        // No metadata for the secondary items - only the essentials
        $icl_translations[] = [
          'element_id' => $icl_object_id,
          'element_type' => $icl_type,
          'language_code' => $lang,
          'trid' => $icl_trid,
          'source_language_code' => $icl_deflang,
        ];
      }
      // op_record("- end lang");
    } // end langs cycle
  } // end $thing->data cycle


  if (op_wpml_enabled()) {
    DB::table('icl_translations')
      ->where('element_type', $icl_type)
      ->whereIn('element_id', $all_icl_object_ids)
      ->delete();
    foreach (array_chunk($icl_translations, 2000) as $chunk) {
      DB::table('icl_translations')->insert($chunk);
    }
  }

  // op_record('cycle ended');
  DB::table($base_tablemeta)->whereIn($base_tablemeta_ref, $object_ids)
    ->where(function($q) {
      $q->where('meta_key', 'like', 'op\\_%')
        ->orWhereIn('meta_key', [
          '_sale_price', '_regular_price', '_price',
          '_sku', '_weight', '_width', '_length', '_height',
          '_thumbnail_id',
        ]);
    })->delete();
  
  // Insert new meta
  foreach (array_chunk($all_meta, 2000) as $chunk) {
    DB::table($base_tablemeta)->insert($chunk);
  }
}



function op_name_to_class(string $res_name) {
  $camel_name = op_snake_to_camel($res_name);
  return "\\Op\\$camel_name";
}

function op_regenerate_items_slug(array $items) {
  foreach ($items as $res_id => $new_res_items) {
    $wp_ids = [];
    foreach ($new_res_items as $op_id => $new_item_langs) {
      foreach ($new_item_langs as $lang => $wp_id) {
        $wp_ids[] = $wp_id;
      }
    }


    $res = collect(op_schema()->resources)->firstWhere('id', $res_id);
    $class = op_name_to_class($res->name);
    $items = $class::unlocalized()->get();

    $start_locale = op_locale();
    foreach ($items as $new_item) {
      op_locale($new_item->getLang());
      $new_slug = apply_filters('op_gen_slug', $new_item);
      if ($new_slug === $new_item || is_null($new_slug) || !mb_strlen($new_slug)) {
        continue;
      }
      if (!is_scalar($new_slug)) {
        op_err("Invalid value returned to hook op_gen_slug: non-scalar", [
          'returned_value' => $new_slug,
        ]);
      }
      $new_item->setSlug($new_slug);
    }
    op_locale($start_locale);
  }
}

function op_import_snapshot_relations($schema, $json, array $all_items) {
  $langs = op_langs();
  foreach ($schema->resources as $res_i => $res) {
    $data = $json->resources[$res_i]->data;
    $meta = [];
    $updated_wp_ids = [];
    $base_tablemeta = $res->is_product ? 'postmeta' : 'termmeta';
    $base_tablemeta_ref = $res->is_product ? 'post_id' : 'term_id';

    foreach ($data as $thing) {
      foreach ($langs as $lang) {
        $wp_id = $all_items[$res->id][$thing->id][$lang];
        $updated_wp_ids[] = $wp_id;
        foreach ($thing->rel_ids as $fid => $tids) {
          $field = $res->id_to_field[$fid];
          foreach ($tids as $rel_tid) {
            $rel_wp_id = $all_items[$field->rel_res_id][$rel_tid][$lang];
            $meta[] = [
              $base_tablemeta_ref => $wp_id,
              'meta_key' => 'oprel_'.$field->name,
              'meta_value' => $rel_wp_id,
            ];
          }
        }
      }
    }

    foreach (array_chunk($updated_wp_ids, 10000) as $chunk) {
      DB::table($base_tablemeta)
        ->whereIn($base_tablemeta_ref, $chunk)
        ->where('meta_key', 'like', 'oprel\\_%')
        ->delete();
    }
    DB::table($base_tablemeta)->insert($meta);
  }
}

function op_generate_data_meta($res, $thing, int $object_id, $field_map, $base_tablemeta_ref) {
  $meta = [];
  // Fields
  foreach ($thing->fields as $fid_lang => $values) {
    $e = explode('_', $fid_lang);
    $f = $field_map[$e[0]];
    $lang = @$e[1];
    if (!$f->is_multiple) {
      $values = [ $values ];
    }
    foreach ($values as $value) {
      $meta[] = [
        $base_tablemeta_ref => $object_id,
        'meta_key' => 'op_'.$f->name.($lang ? "_$lang" : ''),
        'meta_value' => is_scalar($value) ? $value : json_encode($value),
      ];
    }
  }

  // Append the price and other woocommerce metadata
  if ($res->is_product) {
    $meta_map = [
      'price' => ['_sale_price', '_regular_price', '_price'],
      'sku' => ['_sku'],
      'weight' => ['_weight'],
      'width' => ['_width'],
      'length' => ['_length'],
      'height' => ['_height'],
    ];
    foreach ($meta_map as $meta_name => $meta_keys) {
      $price_field = @op_settings()->{"res-{$res->id}-{$meta_name}"};
      if ($price_field && ($f = collect($res->fields)->firstWhere('id', $price_field))) {
        $fid = $f->id;
        if ($f->is_translatable) $fid.= "_".$schema->langs[0];
        $val = @$thing->fields->$fid;
        if ($val !== null) {
          foreach ($meta_keys as $key) {
            $meta[] = [ 'post_id' => $object_id, 'meta_value' => $val, 'meta_key' => $key ];
          }
        }
      }
    }
  }

  return $meta;
} 

function op_gen_model(object $schema, object $res) {
  $camel_name = op_snake_to_camel($res->name);
  $extends = $res->is_product ? 'Post' : 'Term';
  $extends_lc = strtolower($extends);

  $code = "<?php\nnamespace Op; \n";
  $code.= "class $camel_name extends \\OpLib\\$extends {\n";
  $code.= "  public static function boot() {
    parent::boot();
    self::addGlobalScope('opres', function(\$q) {
      \$q->whereRes($res->id);
    });
    self::addGlobalScope('oplang', function(\$q) {
      \$q->localized();
    });
    self::addGlobalScope('opmeta', function(\$q) {
      \$q->loaded();
    });
  }\n";
  $code.= "  public static function getResource() {
    return op_schema()->name_to_res['{$res->name}'];
  }\n";

  foreach ($res->fields as $f) {
    if ($f->type == 'relation') {
      $rel_method = $f->rel_res->is_product ? 'posts' : 'terms';
      $rel_class = op_snake_to_camel($f->rel_res->name);
      $rel_class_primary = $f->rel_res->is_product ? 'ID' : 'term_id';
      $code.= "  function $f->name() {\n";
      $code.= "    return \$this->belongsToMany($rel_class::class, \\OpLib\\{$extends}Meta::class, '{$extends_lc}_id', 'meta_value', null, '$rel_class_primary')\n";
      $code.= "    ->wherePivot('meta_key', 'oprel_$f->name')\n";
      $code.= "    ->orderBy('meta_id');\n";
      $code.= "  }\n";
    }
  }
  $code.= "}\n";
  $file = __DIR__."/db-models/$camel_name.php";
  file_put_contents($file, $code);
}

function op_link(string $path) {
  return plugins_url('', $path).'/'.basename($path);
}

function op_file_url(object $file, $w = null, $h = null, $contain = null) {
  $path = op_file_path($file->token);
  if (is_file($path)) {
    // Original
    $pi = pathinfo($file->name);
    $filename = $pi['filename'];
    $extension = $pi['extension'];
    if ((!$w && !$h)) {
      $extension = ($extension == 'php' ? 'txt' : $extension);
      $hash_v = substr($file->token, 0, 3);
      $target_path = op_file_path("/cache/$filename.$hash_v.$extension");
      if (!file_exists($target_path)) {
        symlink('../'.basename($path), $target_path);
      }
      return op_link($target_path);
    } else {
      $target_path = op_file_path('/cache/'.$file->token);
      $target_path.= '.'.implode('x', [$w ?: '', $h ?: '']);
      if ($contain) {
        $target_path.= '-contain';
      }
      $target_path.= '.'.$file->ext;
      if (!is_file($target_path)) {
        $opts = [
          'crop' => !$contain,
          'format' => $file->ext,
        ];
        if (is_numeric($w)) $opts['width'] = $w;
        if (is_numeric($h)) $opts['height'] = $h;
        if (!op_resize($path, $target_path, $opts)) {
          return op_file_url($file);
        }
      }
      return op_link($target_path);
    }
  }

  // Use onpage as a fallback
  $url = 'https://'.op_getopt('company').'.onpage.it/api/storage/'.$file->token;
  if ($w || $h) {
    $url.= '.'.implode('x', [$w ?: '', $h ?: '']);
    if ($contain) {
      $url.= '-contain';
    }
    $url.= '.png';
  }
  $url.= '?name='.urlencode($file->name);
  return $url;
}

function op_list_files(bool $return_map = false) {
  $files = [];
  foreach (op_schema()->resources as $res) {
    $class = $res->is_product ? 'OpLib\PostMeta' : 'OpLib\TermMeta';
    $meta_col = $res->is_product ? 'post_id' : 'term_id';
    $res_files_query = $class::whereHas('parent', function($q) use ($res) {
      $q->whereRes($res->id);
    });

    $media_fields = [];
    foreach (collect($res->fields)->whereIn('type', ['file', 'image']) as $field) {
      $langs = $field->is_translatable ? op_schema()->langs : [ null ];
      foreach ($langs as $lang) {
        $media_fields[] = op_field_to_meta_key($field, $lang);
      }
    }
    if (empty($media_fields)) continue;
    
    $res_files_query->whereIn('meta_key', $media_fields);

    $res_files = $res_files_query->get()
    ->pluck('meta_value')
    ->map(function($el) {
      return @json_decode($el);
    })
    ->filter(function($x) { return $x && @$x->token; })
    ->values()
    ->all();
    foreach ($res_files as $object_id => $file) {
      if (!isset($files[$file->token])) {
        $files[$file->token] = (object) [
          'info' => (object) $file,
          'term_id' => [],
          'post_id' => [],
        ];
      }
      $files[$file->token]->$meta_col[] = $object_id;
    }
  }
  foreach ($files as &$f) {
    $f->is_imported = is_file(op_file_path($f->info->token));
  }
  if (!$return_map) {
    $files = array_values($files);
  }
  return $files;
}

function op_basename($path) {
  // php basename truncates long file names
    if (preg_match('@^.*[\\\\/]([^\\\\/]+)$@s', $path, $matches)) {
        return $matches[1];
    } else if (preg_match('@^([^\\\\/]+)$@s', $path, $matches)) {
        return $matches[1];
    }
    return '';
}

function op_list_old_files() {
  $db_files = op_list_files(true);

  $glob_ls = op_file_path('*');
  $local_files = array_diff(glob($glob_ls), glob($glob_ls, GLOB_ONLYDIR));
  $local_files = collect($local_files)->map('op_basename')->toArray();
  $files_to_drop = array_filter($local_files, function($token) use ($db_files) {
    return !isset($db_files[$token]);
  });
  return array_values($files_to_drop);
}

function op_drop_old_files() {
  $old_files = op_list_old_files();
  foreach ($old_files as $token) {
    foreach (glob(op_file_path("cache/{$token}*")) as $path) {
      @unlink($path);
    }
    @unlink(op_file_path($token));
  }
}

function op_file_path(string $token = '') {
  return __DIR__."/storage/$token";
}

function op_import_files(array $files) {
  $ret = [];
  foreach ($files as $file) {
    $ret[$file->info->token] = op_import_file($file);
  }
  return $ret;
}

function op_import_file(object $file) {
  $token = $file->info->token;
  $final_path = op_file_path($token);
  $tmp_path = sys_get_temp_dir()."/$token";
  $url = op_endpoint()."/storage/$token";
  
  set_time_limit(0);
  $max_tries = 5;
  while (true) {
    $fp = fopen($tmp_path, 'w');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60*10);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $err = curl_errno($ch);
    curl_close($ch);
    fclose($fp);
    if ($err) {
      if ($max_tries) {
        $max_tries--;
        sleep(3);
        continue;
      } else {
        throw new \Exception("Cannot download file, curl error [$err]");
      }
    } else {
      break;
    }
  }

  rename($tmp_path, $final_path);
  
  $ret = [
    'url' => $url,
    'path' => $final_path,
    'bytes' => filesize($final_path),
  ];
  return $ret['bytes'];
}



function op_endpoint() {
  return "https://".op_getopt('company').'.onpage.it/api';
}

function op_resize($src_path, $dest_path, $params = []) {
  $image = wp_get_image_editor( $src_path ); // Return an implementation that extends WP_Image_Editor
  if (is_wp_error( $image )) return false;
  if (@$params['width'] || @$params['height']) {
    $image->resize( @$params['width'], @$params['height'], !!@$params['crop'] );
  }
  $image->save( $dest_path );
  if (!is_file($dest_path)) {
    return op_resize_fallback($src_path, $dest_path, $params);
  }
  return true;
}

function op_upgrade() {
  $zip_path = __DIR__.'/storage/upgrade.zip';
  $source = 'https://github.com/onpage-dev/woocommerce-onpage/raw/master/woocommerce-onpage.zip';
  $ok = copy($source, $zip_path);
  if (!$ok) op_err('Cannot download update from github');
  require_once(ABSPATH .'/wp-admin/includes/file.php');
  WP_Filesystem();
  $ret = unzip_file($zip_path, __DIR__);
  if ($ret !== true) {
    op_err("Cannot unzip update", [
      'error' => $ret,
    ]);
  }
}

function op_set_post_image($post_id, $path, $filename){
  $upload_dir = wp_upload_dir();
  if(wp_mkdir_p($upload_dir['path'])) {
    $file = $upload_dir['path'] . '/' . $filename;
  } else {
    $file = $upload_dir['basedir'] . '/' . $filename;
  }
  copy($path, $file);


  $wp_filetype = wp_check_filetype($filename, null );
  $attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title' => sanitize_file_name($filename),
    'post_content' => '',
    'post_status' => 'inherit'
  );
  $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
  require_once(ABSPATH . 'wp-admin/includes/image.php');
  $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
  $res1 = wp_update_attachment_metadata( $attach_id, $attach_data );
  $res2 = set_post_thumbnail( $post_id, $attach_id );
}

function op_request(string $name = null) {
  static $req = null;
  if (!$req) {
    $data = file_get_contents('php://input');
    $req = (object) json_decode($data);
  }
  return $name ? @$req->$name ?: @$_REQUEST[$name] : $req;
}

function op_locale($set = null) {
  static $locale = null;
  if (!$locale || $set) $locale = $set ?: substr(get_locale(), 0, 2);
  return $locale;
}

function op_category($key, $value) {
  $item = OpLib\Term::where($key, $value)->first();
  if (!$item) return null;

  $class = op_name_to_class($item->resource->name);
  return $class::find($item->id);
}

function op_product($key, $value) {
  $item = OpLib\Post::where($key, $value)->first();
  if (!$item) return null;

  $class = op_name_to_class($item->resource->name);
  return $class::find($item->id);
}

function op_prod_res(WC_Product $product) {
  $id = $product->get_meta('op_res*');
  if (!$id) return;
  return @op_schema()->id_to_res[$id];
}

function op_field_to_meta_key($field, $lang = null) {
  if (!$lang) $lang = op_locale();
  $key = "op_{$field->name}";
  if ($field->is_translatable) $key.= "_$lang";
  return $key;
}

function op_prod_value(WC_Product $product, $field_name, $lang = null) {

  $res = op_prod_res($product);
  if (!$res) return;

  $field = @$res->name_to_field[$field_name];
  if (!$field) return;

  $key = op_field_to_meta_key($field, $lang);

  $metas = array_values($product->get_meta($key, false));

  $values = array_map(function(WC_Meta_Data $meta) {
    return $meta->get_data()['value'];
  }, $metas);
  return $field->is_multiple ? $values : @$values[0];
}

function op_prod_file(WC_Product $product, $field, $lang = null) {
  $value = op_prod_value($product, $field, $lang);
  if (is_null($value)) return;

  $_m = is_array($value);
  if (!$_m) $value = [$value];
  $value = array_map(function($json) {
    $v = @json_decode($json);
    if (!$v) throw new \Exception("cannot parse $json");
    return new OpLib\File($v);
  }, $value);
  return $_m ? $value : @$value[0];
}



function op_resize_fallback($src_path, $dest_path, $params = []) {
  /**
  * Images scaling.
  * @param string  $src_path Path to initial image.
  * @param string $dest_path Path to save new image.
  * @param array $params [optional] Must be an associative array of params
  * $params['width'] int New image width.
  * $params['height'] int New image height.
  * $params['constraint'] array.$params['constraint']['width'], $params['constraint'][height]
  * If specified the $width and $height params will be ignored.
  * New image will be resized to specified value either by width or height.
  * $params['aspect_ratio'] bool If false new image will be stretched to specified values.
  * If true aspect ratio will be preserved an empty space filled with color $params['rgb']
  * It has no sense for $params['constraint'].
  * $params['crop'] bool If true new image will be cropped to fit specified dimensions. It has no sense for $params['constraint'].
  * $params['rgb'] Hex code of background color. Default 0xFFFFFF.
  * $params['quality'] int New image quality (0 - 100). Default 100.
  * @return bool True on success.
  */
  $width = !empty($params['width']) ? $params['width'] : null;
  $height = !empty($params['height']) ? $params['height'] : null;
  $constraint = !empty($params['constraint']) ? $params['constraint'] : false;
  $rgb = !empty($params['rgb']) ?  $params['rgb'] : 0xFFFFFF;
  $quality = !empty($params['quality']) ?  $params['quality'] : 80;
  $aspect_ratio = isset($params['aspect_ratio']) ?  $params['aspect_ratio'] : true;
  $crop = isset($params['crop']) ?  $params['crop'] : true;

  if (!file_exists($src_path)) {
    return false;
  }

  if (!is_dir($dir = dirname($dest_path))) {
    mkdir($dir);
  }

  $img_info = getimagesize($src_path);
  if ($img_info === false) {
    // die('xx: no size');
    return false;
  }

  $ini_p = $img_info[0] / $img_info[1];
  if ($constraint) {
    $con_p = $constraint['width'] / $constraint['height'];
    $calc_p = $constraint['width'] / $img_info[0];

    if ($ini_p < $con_p) {
      $height = $constraint['height'];
      $width = $height * $ini_p;
    } else {
      $width = $constraint['width'];
      $height = $img_info[1] * $calc_p;
    }
  } else {
    if (!$width && $height) {
      $width = ($height * $img_info[0]) / $img_info[1];
    } elseif (!$height && $width) {
      $height = ($width * $img_info[1]) / $img_info[0];
    } elseif (!$height && !$width) {
      $width = $img_info[0];
      $height = $img_info[1];
    }
  }

  $mime = $img_info['mime'];
  preg_match('/^image\/([a-z]+)$/i', "$mime", $match);
  $ext = strtolower(@$match[1]);
  if (!in_array($ext, ['jpeg', 'jpg', 'png', 'gif'])) {
    // die('xx: wrong format');
    return false;
  }
  $output_format = ($ext == 'jpg') ? 'jpeg' : $ext;

  $format = strtolower(substr($img_info['mime'], strpos($img_info['mime'], '/') + 1));
  $icfunc = 'imagecreatefrom'.$format;

  $iresfunc = 'image'.$output_format;

  if (!function_exists($icfunc)) {
    die('error: install gd library - no function: '.$icfunc);
    return false;
  }

  $dst_x = $dst_y = 0;
  $src_x = $src_y = 0;
  $res_p = $width / $height;
  if ($crop && !$constraint) {
    $dst_w = $width;
    $dst_h = $height;
    if ($ini_p > $res_p) {
      $src_h = $img_info[1];
      $src_w = $img_info[1] * $res_p;
      $src_x = ($img_info[0] >= $src_w) ? floor(($img_info[0] - $src_w) / 2) : $src_w;
    } else {
      $src_w = $img_info[0];
      $src_h = $img_info[0] / $res_p;
      $src_y = ($img_info[1] >= $src_h) ? floor(($img_info[1] - $src_h) / 2) : $src_h;
    }
  } else {
    if ($ini_p > $res_p) {
      $dst_w = $width;
      $dst_h = $aspect_ratio ? floor($dst_w / $img_info[0] * $img_info[1]) : $height;
      $dst_y = $aspect_ratio ? floor(($height - $dst_h) / 2) : 0;
    } else {
      $dst_h = $height;
      $dst_w = $aspect_ratio ? floor($dst_h / $img_info[1] * $img_info[0]) : $width;
      $dst_x = $aspect_ratio ? floor(($width - $dst_w) / 2) : 0;
    }
    $src_w = $img_info[0];
    $src_h = $img_info[1];
  }

  $isrc = $icfunc($src_path);
  $idest = imagecreatetruecolor($width, $height);
  if (($format == 'png' || $format == 'gif') && $output_format == $format) {
    imagealphablending($idest, false);
    imagesavealpha($idest, true);
    imagefill($idest, 0, 0, IMG_COLOR_TRANSPARENT);
    imagealphablending($isrc, true);
    $quality = 0;
  } else {
    imagefill($idest, 0, 0, $rgb);
  }
  imagecopyresampled($idest, $isrc, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
  $res = $iresfunc($idest, $dest_path, $quality);

  imagedestroy($isrc);
  imagedestroy($idest);

  return $res;
}


function op_reset_data(callable $post_scope = null, callable $term_scope = null) {
  $wpml_enabled = op_wpml_enabled();
  ini_set('memory_limit','1G');
  set_time_limit(30);
  try {
    // Delete products
    while (true) {
      $query = \OpLib\Post::query()
        ->unfiltered()
        ->where('post_type', 'product')
        ->limit(2000);
      if ($post_scope) {
        $post_scope($query);
      }
      $post_ids = $query->pluck('ID');
      if ($post_ids->isEmpty()) break;
      if ($wpml_enabled) {
        DB::table('icl_translations')
          ->where('element_type', 'post_product')
          ->whereIn('element_id', $post_ids)
          ->delete();
      }
      
      DB::table('postmeta')
        ->whereIn('post_id', $post_ids)
        ->delete();

      DB::table('posts')
        ->whereIn('ID', $post_ids)
        ->delete();
    }


    // Delete terms and taxonomies
    while (true) {
      $query = OpLib\TermTaxonomy::query()
        ->where('taxonomy', 'product_cat')
        ->select(['term_taxonomy_id', 'term_id'])
        ->limit(2000);
      
      if ($term_scope) {
        $query->whereHas('term', function($query) use ($term_scope) {
          $query->unfiltered();
          $term_scope($query);
        });
      }
      $taxonomies = $query->get();

      if ($taxonomies->isEmpty()) break;
      op_record("Terms to delete: ".$taxonomies->pluck('term_id')->implode('-'));
      if ($wpml_enabled) {
        DB::table('icl_translations')
          ->where('element_type', 'tax_product_cat')
          ->whereIn('element_id', $taxonomies->pluck('term_taxonomy_id'))
          ->delete();
      }

      DB::table('term_taxonomy')
        ->whereIn('term_taxonomy_id', $taxonomies->pluck('term_taxonomy_id'))
        ->delete();

      DB::table('termmeta')
        ->whereIn('term_id', $taxonomies->pluck('term_id'))
        ->delete();

      DB::table('terms')
        ->whereIn('term_id', $taxonomies->pluck('term_id'))
        ->delete();
    }
  } catch (\Throwable $e) {
    op_err("Something went wrong: {$e->getMessage()}", [
      'exception' => $e,
    ]);
  }
}