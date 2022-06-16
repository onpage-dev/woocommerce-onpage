<?php
if (!defined( 'ABSPATH' )) exit;

// error_reporting(E_ALL ^ E_NOTICE);
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once __DIR__.'/vendor/autoload.php';
// use Illuminate\Database\Capsule\Manager as DB;

use OpLib\Post;
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
  global $table_prefix;
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
  if (op_settings()->migration < 60) {
    $statements = [];
      $statements[] = "SET NAMES utf8";
      $statements[] = "SET time_zone = '+00:00'";
      $statements[] = "SET foreign_key_checks = 0";
      $statements[] = "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'";


      $statements[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}op_things` (
        `id` int(11) NOT NULL,
        `resource_id` int(11) NOT NULL,
        `op_order` float NOT NULL,
        PRIMARY KEY (`id`),
        KEY `resource_id_op_order` (`resource_id`,`op_order`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

      $statements[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}op_thingmeta` (
        `meta_id` int(11) NOT NULL AUTO_INCREMENT,
        `thing_id` int(11) NOT NULL,
        `meta_key` varchar(50) NOT NULL,
        `meta_value` text NOT NULL,
        PRIMARY KEY (`meta_id`),
        KEY `meta_key_meta_value` (`meta_key`,`meta_value`(255)),
        KEY `thing_id_meta_key_meta_value` (`thing_id`,`meta_key`,`meta_value`(255)),
        CONSTRAINT `{$table_prefix}op_thingmeta_ibfk_1` FOREIGN KEY (`thing_id`) REFERENCES `{$table_prefix}op_things` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";


      foreach ($statements as $st) {
        DB::statement($st);
      }
      op_setopt('migration', 60);
      
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

function op_latest_snapshot_token(object $sett = null) {
  if (!$sett) $sett = op_settings();
  $info = op_download_json("https://{$sett->company}.onpage.it/api/view/{$sett->token}/dist") or op_ret(['error' => 'Cannot access API - check your settings']);
  if (!@$info->token) {
    op_ret(['error' => 'No snapshot present, generate it on OnPage']);
  }
  return $info->token;
}

function op_download_snapshot(string $token) {
  $sett = op_settings();

  // op_record('start import');
  $db = op_download_json("https://{$sett->company}.onpage.it/api/storage/{$token}") or op_ret(['error' => 'Cannot download snapshot']);
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

function op_write_json($name, $json) {
  return file_put_contents(wp_upload_dir()['basedir']."/on-page-$name.json", json_encode($json));
}
function op_read_json($name) {
  return @json_decode(file_get_contents(wp_upload_dir()['basedir']."/on-page-$name.json"));
}

function op_label($res, string $lang = null) {
  if (!is_object($res) || !isset($res->labels)) {
    throw new Exception('First parameter for op_label must be resource or field');
  }
  if (!$lang) $lang = op_locale();
  return $res->labels->$lang ?? $res->labels->{op_schema()->default_lang} ?? '?';
}
function op_description($res, string $lang = null) {
  if (!is_object($res) || !isset($res->descriptions)) {
    throw new Exception('First parameter for op_label must be resource or field');
  }
  if (!$lang) $lang = op_locale();
  return $res->descriptions->$lang ?? $res->descriptions->{op_schema()->default_lang} ?? '?';
}
function op_ignore_user_scopes(bool $set = null) {
  static $safe = false;
  if (!is_null($set)) {
    $safe = $set;
  }
  return $safe;
}
function op_stored_schema() {
  return op_getopt('schema') ?? op_read_json('schema');
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
    op_write_json('schema', $set);
    $schema = null;
  }
  if (!$schema) {
    $schema = op_stored_schema();
    if (!$schema) return null;
    $schema->id_to_res = [];
    $schema->id_to_folder = [];
    $schema->id_to_field = [];
    $schema->name_to_res = [];
    foreach ($schema->resources as $res) {
      $schema->id_to_res[$res->id] = $res;
      $schema->name_to_res[$res->name] = $res;
      $res->id_to_field = [];
      $res->name_to_field = [];
      foreach ($res->fields as $field) {
        $schema->id_to_field[$field->id] = $field;
        $res->id_to_field[$field->id] = $field;
        $res->name_to_field[$field->name] = $field;
      }
      foreach ($res->field_folders as $folder) {
        $schema->id_to_folder[$folder->id] = $folder;
        $folder->fields = [];
        foreach ($folder->fids as $field_id) {
          $folder->fields[] = $schema->id_to_field[$field_id];
        }
      }
    }

    // Fields: res, rel_res, rel_field
    foreach ($schema->id_to_field as $field) {
      $field->res = $schema->id_to_res[$field->resource_id];
      if ($field->type == 'relation') {
        $field->rel_res = $schema->id_to_res[$field->rel_res_id];
        $field->rel_field = $schema->id_to_field[$field->rel_field_id];
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
  if (count($steps) == 1) {
    @unlink(op_import_log_path());
  }
  file_put_contents(op_import_log_path(), "$message\n", FILE_APPEND);
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
  $slug = sanitize_title($title);

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

function op_import_snapshot(bool $force_slug_regen = false, string $restore_previous_snapshot=null, bool $force_import = false, bool $regen_snapshot = false) {
  op_record("Starting import");
  op_record("Server time: ".date('Y-m-d H:i:s'));

  if (function_exists('sem_get')) {
    $semaphore = sem_get(333666333, 1, 0666, true);
    if(!$semaphore) {
      op_err('Could not create semaphore, please verify sem_get() is enabled or contact support');
    }
    $semaphore_acquired = sem_acquire($semaphore, true);
    if (!$semaphore_acquired) {
      op_err('Another import is already in progress, please try later');
    }
  } else {
    op_record('!!! php is not supporting sem_get() so the plugin cannot make sure the import is atomic !!!');
  }

  if (op_wpml_enabled()) {
    op_locale(op_wpml_default());
    do_action( 'wpml_switch_language', op_wpml_default() );
  }

  // Regenerate the snapshot
  if ($regen_snapshot) {
    op_record('Generating a fresh snapshot...');
    $sett = op_settings();
    op_download_json("https://{$sett->company}.onpage.it/api/view/{$sett->token}/generate-snapshot") or die("Error: canot regenerate snapshot - check your settings\n");
    op_record('done');
  }
  
  op_remove_corrupted();

  ini_set('memory_limit','2G');
  set_time_limit(600);
  ini_set('max_execution_time', '600');
  $token_to_import = null;
  $schema_json = null;
  if ($restore_previous_snapshot) {
    $schema_json = op_get_saved_snapshot($restore_previous_snapshot);
  } else {
    $token_to_import = op_latest_snapshot_token();

    $data_changed = $token_to_import != op_getopt('last_import_token');


    if (!$data_changed) {
      op_record("Current data is already up to date");
      if (!$force_import) {
        op_record("Skipping import");
        return;
      } else {
        op_record("Import anyway");
      }
    }


    $schema_json = op_download_snapshot($token_to_import);
    op_record('download completed');

    // Create imported_at field
    $schema_json->imported_at = date('Y-m-d H:i:s');

    // Overwrite which resource must be imported as a product
    // This is a legacy hook
    $overwrite_products = apply_filters('on_page_product_resources', null);
    if (is_array($overwrite_products)) {
      foreach ($schema_json->resources as $res) {
        $res->is_product = in_array($res->name, $overwrite_products);
      }
    }

    // This is the newer hook which defines how to import resources
    $overwrite_things = apply_filters('op_resource_types', null);
    if (is_array($overwrite_things)) {
      foreach ($schema_json->resources as $res) {
        $type = 'thing';
        if (isset($overwrite_things[$res->name])) {
          $type = $overwrite_things[$res->name];
          if (!in_array($type, ['thing', 'term', 'post'])) {
            throw new \Exception("Invalid type for resource $res->name: $type");
          }
        }

        $res->is_thing = $type == 'thing';
        $res->is_product = $type == 'post';
      }
    }

    // Setup php_class, php_metaclass, op_type for all the resources
    foreach ($schema_json->resources as $res) {
      $res->php_class = \OpLib\Term::class;
      $res->php_metaclass = \OpLib\TermMeta::class;
      $res->op_type = 'term';
      if ($res->is_product) {
        $res->op_type = 'post';
        $res->php_class = \OpLib\Post::class;
        $res->php_metaclass = \OpLib\PostMeta::class;
      } elseif ($res->is_thing) {
        $res->op_type = 'thing';
        $res->php_class = \OpLib\Thing::class;
        $res->php_metaclass = \OpLib\ThingMeta::class;
      }
    }

    // Clone this element
    $snapshot_to_save = clone $schema_json;
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

  op_record('Deleting old categories...');
  $disabled_count = op_disable_old_categories($all_items);
  op_record("deleted $disabled_count categories");

  op_record('Disabling old products...');
  $disabled_count = op_disable_old_products($all_items);
  op_record("disabled $disabled_count products");

  op_record('Deleting old things...');
  $disabled_count = op_delete_old_things($all_items);
  op_record("deleted $disabled_count things");

  op_record('Deleting orphaned metadata');
  op_delete_orphan_meta();

  op_record('Creating php models');
  foreach ($schema->resources as $res) op_gen_model($schema, $res);
  op_record('done');

  op_record('Generating slugs...');
  op_regenerate_import_slug($force_slug_regen ? $all_items : $new_items);
  op_record('done');

  op_record('Importing relations...');
  op_link_imported_data($schema);
  op_record('done');
  
  op_import_gallery($schema);

  flush_rewrite_rules();
  op_record('permalinks flushed');

  if (isset($snapshot_to_save)) {
    op_record('Storing snapshot...');
    op_save_snapshot_file($snapshot_to_save);
    op_del_old_snapshots();
    op_record('done');
  }

  op_setopt('last_import_token', $token_to_import);
  do_action('op_import_completed');
}

function op_import_gallery($schema) {
  foreach ($schema->resources as $res) {
    if ($res->op_type != 'post') continue;
    
    $image_field_id = op_getopt("res-{$res->id}-image");
    $field = $res->id_to_field[$image_field_id] ?? null;
    if (!$field || $field->type != 'image') continue;

    op_record("Importing images for $res->label ($field->name)...");

    $class = op_name_to_class($res->name);

    // op_record("Importing images for $res->label...");
    foreach ($class::query()->unlocalized()->whereLang(op_langs()[0])->whereField($field->name, 'like', '%')->get() as $item) {
      // op_record("Importing image for $item->post_title...");
      $file = $item->file($field->name, $item->getLang());
      // print_r($file);
      if (is_array($file)) $file = $file[0] ?? null;
      if (is_null($file)) continue;
      if ($file instanceof OpLib\File) {
        // op_record("Importing image for $item->post_title...");
        op_set_product_featured_image($file, $item->id);
      }
    }
    op_record("done");
  }
}

function op_delete_orphan_meta() {
  global $table_prefix;
  return [
    'postmeta' => (int) DB::statement("delete FROM `{$table_prefix}postmeta` where not exists(select 1 from `{$table_prefix}posts` where ID=post_id)"),
    'termmeta' => (int) DB::statement("delete FROM `{$table_prefix}termmeta` where not exists(select 1 from `{$table_prefix}terms` t where `{$table_prefix}termmeta`.term_id=t.term_id)"),
  ];
}

function op_disable_old_products(array $imported_items) :int {
  $posts_to_remove = OpLib\Post::pluck('ID')->flip();
  foreach ($imported_items as $res_id => $res_items) {
    $res = collect(op_schema()->resources)->firstWhere('id', $res_id);
    if ($res->op_type != 'post') continue;
    foreach ($res_items as $op_id => $new_item_langs) {
      foreach ($new_item_langs as $lang => $wp_id) {
        $posts_to_remove->forget($wp_id);
      }
    }
  }

  // Set posts as drafts
  foreach ($posts_to_remove->keys()->chunk(1000) as $chunk) {
    OpLib\Post::whereIn('ID', $chunk)->update([
      'post_status' => 'draft',
    ]);
  }
  return $posts_to_remove->count();
}
function op_disable_old_categories(array $imported_items) :int {
  $tax_to_remove = OpLib\TermTaxonomy::get()->keyBy('term_id');
  
  foreach (op_get_static_terms() as $wp_id) {
    $tax_to_remove->forget($wp_id);
  }

  foreach ($imported_items as $res_id => $res_items) {
    $res = collect(op_schema()->resources)->firstWhere('id', $res_id);
    if ($res->op_type != 'term') continue;
    foreach ($res_items as $op_id => $new_item_langs) {
      foreach ($new_item_langs as $lang => $wp_id) {
        $tax_to_remove->forget($wp_id);
      }
    }
  }

  // Set posts as drafts
  op_delete_taxonomies_and_terms($tax_to_remove);
  return $tax_to_remove->count();
}

function op_get_static_terms() {

  $static_parents = apply_filters('op_import_relations', null) ?: [];
  $all_ids = [];
  foreach ($static_parents as $term_id) {
    if (!is_numeric($term_id)) continue;

    if (!op_wpml_enabled()) {
      $all_ids[] = $term_id;
    } else {
      foreach (op_wpml_langs() as $lang) {
        $all_ids[] = apply_filters('wpml_object_id', $term_id, 'product_cat', true, $lang);
      }
    }
  }
  return $all_ids;
}
function op_delete_old_things(array $imported_items) :int {
  $things_to_remove = OpLib\Thing::get()->keyBy('id');
  foreach ($imported_items as $res_id => $res_items) {
    $res = collect(op_schema()->resources)->firstWhere('id', $res_id);
    if ($res->op_type != 'thing') continue;
    foreach ($res_items as $op_id => $new_item_langs) {
      foreach ($new_item_langs as $lang => $wp_id) {
        $things_to_remove->forget($wp_id);
      }
    }
  }
  foreach ($things_to_remove->keys()->chunk(1000) as $chunk) {
    OpLib\Thing::query()->whereIn('id', $chunk)->delete();
  }
  return $things_to_remove->count();
}

function op_link_imported_data($schema) {
  $relations = apply_filters('op_import_relations', null);
  if (empty($relations)) return;

  if (op_wpml_enabled()) {
    op_locale(op_wpml_default());
    do_action( 'wpml_switch_language', op_wpml_default() );
  }

  foreach ($relations as $resource_name => $parent_relation) {
    $res = collect($schema->resources)->firstWhere('name', $resource_name);
    if (!$res) op_err("Cannot find resource $resource_name for hook op_import_relations; available resources: ".collect($schema->resources)->pluck('name')->implode(', '));
    $class = op_name_to_class($res->name);

    // Static parent -> static category id
    if (is_int($parent_relation)) {
      foreach ($class::query()->get() as $child_term) {
        // make sure this is the primary language term
        if (op_wpml_enabled()) {
          $parent_relation = apply_filters('wpml_object_id', $parent_relation, 'product_cat', true, op_wpml_default());
        }

        // Call wp_update_term
        $ret = wp_update_term($child_term->id, 'product_cat', [
          'parent' => $parent_relation,
          'slug' => $child_term->slug,
        ]);
        if ($ret instanceof \WP_Error) {
          op_err("Error while setting parent for a relation", ['wp_err' => $ret]);
        }
      }
    } else {
      // Parent relation is a relation name
      $rel_field = collect($res->fields)->where('type', 'relation')->firstWhere('name', $parent_relation);
      if (!$rel_field) op_err("Cannot find relation $parent_relation for hook op_import_relations");
  
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

function op_locale_to_lang(string $locale) {
  $locale = explode('-', $locale)[0];
  $locale = explode('_', $locale)[0];
  return $locale;
}

function op_import_resource(object $db, object $res, array $res_data, array $langs, string $imported_at, array &$all_items, array &$new_items) {
  $php_class = $res->php_class;
  /** @var \OpLib\MetaFunctions */
  $php_class = new $php_class;
  
  $php_metaclass = $res->php_metaclass;
  $php_metaclass = new $php_metaclass;


  $lab_id = op_getopt("res-{$res->id}-name");
  $lab = collect($res->fields)->firstWhere('id', $lab_id)
     ?? collect($res->fields)->whereNotIn('type', ['relation', 'file', 'image'])->first();
     

  $description_field = $res->id_to_field[op_getopt("res-{$res->id}-description")] ?? null;

  $lab_img = $php_class->isThing() ? null : collect($res->fields)->where('type', 'image')->first();
  $base_table = $php_class->getTableWithoutPrefix();
  $base_table_key = $php_class->primaryKey;
  $base_tablemeta_ref = $php_class::$meta_ref;
  $base_class = $res->php_class;
  $icl_type = !op_wpml_enabled() || $res->is_thing ? null : ($res->is_product ? 'post_product' : 'tax_product_cat');
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

  $icl_trid = op_wpml_enabled() ? DB::table('icl_translations')->max('trid') + 2 : 0;

  foreach ($res_data as $thing_i => $thing) {
    if ($thing_i && $thing_i%100 == 0) {
      op_record("- $thing_i/".count($res_data));
    }
    $icl_primary_id = null;
    $icl_trid++;

    // Create the item in each language - first language is the primary one
    foreach ($php_class->isThing() ? [null] : $langs as $lang) {
      // op_record("- lang $lang");
      $is_primary = !$icl_primary_id;
      $lab_img_field = $lab_img ? $lab_img->id.($lab_img->is_translatable ? "_{$db->langs[0]}" : '') : null;
      $lab_field = $lab ? $lab->id.($lab->is_translatable ? "_".op_locale_to_lang($lang ?: $db->langs[0]) : '') : null;
      $description_field_alias = $description_field ? $description_field->id.($description_field->is_translatable ? "_".op_locale_to_lang($lang ?: $db->langs[0]) : '') : null;

      $label = @$thing->fields->$lab_field;
      if (is_null($label)) $label = 'unnamed';
      if (is_array($label)) $label = implode(' - ', $label);
      if (!is_scalar($label)) $label = json_encode($label);

      $description = (string) @$thing->fields->$description_field_alias;
      if (is_array($description)) $description = implode('<br/>', $description);
      if (!is_scalar($description)) $description = json_encode($description);


      // Look for the object if it exists already
      $object = @$current_objects["{$thing->id}-{$lang}"];


      // Prepare data
      $data = null;
      if ($php_class->isThing()) {
        $data = [
          'id' => $thing->id,
          'resource_id' => $thing->resource_id,
          'op_order' => $thing_i,
        ];
      } elseif ($php_class->isPost()) {
        $data = [
          'post_author' => 1,
          'post_date' => $object ? $object->post_date : date('Y-m-d H:i:s'),
          'post_date_gmt' => $object ? $object->post_date_gmt : date('Y-m-d H:i:s'),
          'post_content' => (string) $description,
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
          'post_modified' => $object ? $object->post_modified : $imported_at,
          'post_modified_gmt' => $object ? $object->post_modified_gmt : $imported_at,
          'post_content_filtered' => '',
          'post_parent' => 0,
          'guid' => '',
          'menu_order' => $thing_i,
          'post_type' => 'product',
          'post_mime_type' => '',
          'comment_count' => 0,
        ];
      } elseif ($php_class->isTerm()) {
        $data = [
          'name' => $label,
          'slug' => $object
            ? $object->slug
            : sanitize_title_with_dashes("{$thing->id}-$label-$lang"),
          'term_group' => 0,
          'op_order' => $thing_i,
        ];
      }
         
      // op_record("- ready to upsert");
      // Create or update the object
      if ($object) {
        $object_id = $object->$base_table_key;
        $data_to_update = [];
        foreach ($data as $column => $new_value) {
          if ($object->$column != $new_value) {
            $data_to_update[$column] = $new_value;
          }
        }
        if (count($data_to_update)) {
          if ($res->is_product) {
            $data_to_update['post_modified'] = $imported_at;
            $data_to_update['post_modified_gmt'] = $imported_at;
          }
          DB::table($base_table)->where($base_table_key, $object_id)->update($data_to_update);
        }
        // op_record("- updated");
      } else {
        $object_id = DB::table($base_table)->insertGetId($data);

        // Things use the same onpage id
        if ($php_class->isThing()) $object_id = $data['id'];

        // Check for errors
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
      if ($php_class->isTerm()) {
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
      if ($php_class->isPost()) {
        DB::table('term_relationships')->where('object_id', $object_id)->delete();
        wp_set_object_terms($object_id, 'simple', 'product_type');
        // op_record("- wp_set_object_terms");
      }

      // Calculate base meta
      $base_meta = [];
      // These fields are immutable and only created during the first import
      if (!$object) {
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
      }
      
      if ($thing->default_folder_id) {
        $base_meta[] = [
          $base_tablemeta_ref => $object_id,
          'meta_key' => 'op_default_folder_id*',
          'meta_value' => $thing->default_folder_id,
        ];
      }

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
      $all_meta = array_merge($all_meta, op_generate_data_meta($db, $res, $thing, $object_id, $field_map, $base_tablemeta_ref));

      // If this is the primary language
      if ($icl_type) {
        $icl_object_id = $res->is_product ? $object_id : $tax_id;
        $all_icl_object_ids[] = $icl_object_id;
        if (!$icl_object_id) {
          op_err("cannot get taxonomy id: $object_id-$tax_id");
        }

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
      } // end icl
      // op_record("- end lang");
    } // end langs cycle
  } // end $thing->data cycle


  if ($icl_type) {
    DB::table('icl_translations')
      ->where('element_type', $icl_type)
      ->whereIn('element_id', $all_icl_object_ids)
      ->delete();
    foreach (array_chunk($icl_translations, 2000) as $chunk) {
      DB::table('icl_translations')->insert($chunk);
    }
  }

  // op_record('cycle ended');
  $php_metaclass->whereIn($base_tablemeta_ref, $object_ids)
    ->whereNotIn('meta_key', ['op_lang*', 'op_id*', 'op_res*'])
    ->where(function($q) {
      $q->where('meta_key', 'like', 'op\\_%')
        ->orWhereIn('meta_key', [
            '_price', // do not remove this line
            '_regular_price',
            '_sale_price',
            '_sale_price_dates_from',
            '_sale_price_dates_to',
            '_sku',
            '_weight',
            '_width',
            '_length',
            '_height',
            '_thumbnail_id',
        ]);
    })->delete();

  // Insert new meta
  foreach (array_chunk($all_meta, 2000) as $chunk) {
    $php_metaclass->insert($chunk);
  }
}

function op_remove_corrupted() {
  $deleted_posts = OpLib\Post::withoutGlobalScopes()
    ->where('post_type', 'product')
    ->where(function($q) {
      $q->whereDoesntHave('meta', function($meta_query) { $meta_query->where('meta_key', 'op_id*'); });
      $q->orWhereDoesntHave('meta', function($meta_query) { $meta_query->where('meta_key', 'op_res*'); });
      $q->orWhereDoesntHave('meta', function($meta_query) { $meta_query->where('meta_key', 'op_lang*'); });
    })
    ->delete();
  if ($deleted_posts) op_record("Deleted corrupted posts: $deleted_posts");
  
  $deleted_terms = OpLib\Term::withoutGlobalScopes()
    ->whereNotIn('term_id', op_get_static_terms())
    ->whereHas('taxonomies', function($tax_query) { $tax_query->where('taxonomy', 'product_cat'); })
    ->where(function($q) {
      $q->whereDoesntHave('meta', function($meta_query) { $meta_query->where('meta_key', 'op_id*'); });
      $q->orWhereDoesntHave('meta', function($meta_query) { $meta_query->where('meta_key', 'op_res*'); });
      $q->orWhereDoesntHave('meta', function($meta_query) { $meta_query->where('meta_key', 'op_lang*'); });
    })
    ->delete();
  if ($deleted_terms) op_record("Deleted corrupted terms: $deleted_terms");
}


function op_name_to_class(string $res_name) {
  $camel_name = op_snake_to_camel($res_name);
  return "\\Op\\$camel_name";
}

function op_regenerate_import_slug(array $items) {
  foreach ($items as $res_id => $new_res_items) {
    $res = collect(op_schema()->resources)->firstWhere('id', $res_id);
    if ($res->op_type == 'thing') continue;


    $wp_ids = [];
    foreach ($new_res_items as $op_id => $new_item_langs) {
      foreach ($new_item_langs as $lang => $wp_id) {
        $wp_ids[] = $wp_id;
      }
    }

    $class = op_name_to_class($res->name);
    foreach (array_chunk($wp_ids, 100) as $wp_id_chunk) {
        $items = $class::unlocalized()->whereIn($class::getPrimaryKey(), $wp_id_chunk)->get();
        op_regenerate_items_slug($items);
    }
  }
}

function op_regenerate_all_slugs() {
  op_debug();
  foreach (op_schema()->resources as $res) {
    // if ($res->name != 'categorie') continue;
    $class = op_name_to_class($res->name);
    op_record("regenerating slugs for $class");
    $class::unlocalized()->chunk(200, function($items) {
      op_regenerate_items_slug($items);
    });
  }
}

function op_regenerate_items_slug($items) {

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

function op_import_snapshot_relations($schema, $json, array $all_items) {
  $langs = op_langs();
  $resources = collect($schema->resources)->keyBy('id');
  foreach ($schema->resources as $res_i => $res) {
    $data = $json->resources[$res_i]->data;
    $meta = [];
    $updated_wp_ids = [];

    $php_class = $res->php_class;
    /** @var \OpLib\MetaFunctions */
    $php_class = new $php_class;
  
    $php_metaclass = $php_class::$meta_class;
    $php_metaclass = new $php_metaclass;
    
    $base_tablemeta_ref = $php_class::$meta_ref;

    $fid_to_relation_langs = [];

    foreach ($data as $thing) {
      // Things have only the null lang
      foreach ($all_items[$res->id][$thing->id] as $lang => $wp_id) {
        $updated_wp_ids[] = $wp_id;
        foreach ($thing->rel_ids as $fid => $tids) {
          $field = $res->id_to_field[$fid];
          $target_res = $resources[$field->rel_res_id];
          // thing => non thing = duplicate relations by lang (null -> en, null -> it, ...)
          // thing => thing = no duplication (null -> null)
          // non thing => non thing = no duplication (en -> en)
          // non thing => thing = no duplication (en -> null)
          if (!isset($fid_to_relation_langs["$fid-$lang"])) {
            $relation_langs = null;
            if ($res->is_thing && !$target_res->is_thing) {
              $relation_langs = $langs;
            } elseif ($res->is_thing && $target_res->is_thing) {
              $relation_langs = [null];
            } elseif (!$res->is_thing && !$target_res->is_thing) {
              $relation_langs = [$lang];
            } elseif (!$res->is_thing && $target_res->is_thing) {
              $relation_langs = [null];
            }
            $fid_to_relation_langs["$fid-$lang"] = $relation_langs;
          }
          $relation_langs = $fid_to_relation_langs["$fid-$lang"];
          foreach ($tids as $rel_tid) {
            foreach ($relation_langs as $rel_lang) {
              $rel_wp_id = $all_items[$target_res->id][$rel_tid][$rel_lang];
              $meta[] = [
                $base_tablemeta_ref => $wp_id,
                'meta_key' => 'oprel_'.$field->name,
                'meta_value' => $rel_wp_id,
              ];
            }
          }
        }
      }
    }

    foreach (array_chunk($updated_wp_ids, 10000) as $chunk) {
      $php_metaclass
        ->whereIn($base_tablemeta_ref, $chunk)
        ->where('meta_key', 'like', 'oprel\\_%')
        ->delete();
    }
    $php_metaclass->insert($meta);
  }
}

function op_generate_data_meta($schema, $res, $thing, int $object_id, $field_map, $base_tablemeta_ref) {
  $meta = [];
  // Fields
  foreach ($thing->fields as $field_hc_name => $values) {
    $e = explode('_', $field_hc_name);
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
      'price' => '_regular_price',
      'discounted-price' => '_sale_price',
      'discounted-start-date' => '_sale_price_dates_from',
      'discounted-end-date' => '_sale_price_dates_to',
      'sku' => '_sku',
      'weight' => '_weight',
      'width' => '_width',
      'length' => '_length',
      'height' => '_height',
    ];

    // Fill values using the mapping above
    $values = [];
    foreach ($meta_map as $meta_name => $meta_key) {
      $values[$meta_key] = null;
      $op_fid = op_getopt("res-{$res->id}-{$meta_name}");
      if ($op_fid && ($f = collect($res->fields)->firstWhere('id', $op_fid))) {
        $fid = $f->id;
        if ($f->is_translatable) $fid.= "_".$schema->langs[0];
        $val = @$thing->fields->$fid;
        if (!is_null($val)) {
          $values[$meta_key] = $val;
        }
      }
    }

    $sale_period_active = true;
    if ($values['_sale_price_dates_from']) {
      $values['_sale_price_dates_from'] = strtotime($values['_sale_price_dates_from']);
      if (time() < $values['_sale_price_dates_from']) $sale_period_active = false;
    }
    if ($values['_sale_price_dates_to']) {
      $values['_sale_price_dates_to'] = strtotime($values['_sale_price_dates_to']);
      if (time() > $values['_sale_price_dates_to']) $sale_period_active = false;
    }
    // Calculate final (real) price
    $values['_price'] = $values['_sale_price'] && $sale_period_active ? $values['_sale_price'] : $values['_regular_price'];

    foreach ($values as $meta_key => $value) {
      if (is_null($value)) continue;
      $meta[] = [ 'post_id' => $object_id, 'meta_value' => $value, 'meta_key' => $meta_key ];
    }
  }

  return $meta;
}

function op_gen_model(object $schema, object $res) {
  $camel_name = op_snake_to_camel($res->name);
  $extends = $res->php_class;

  $extends_lc = strtolower(basename(str_replace('\\', '/', $extends)));

  $code = "<?php\nnamespace Op; \n";
  $code.= "class $camel_name extends \\{$extends} {\n";
  $code.= "  public static function boot() {
    parent::boot();
    self::addGlobalScope('_op-lang', function(\$q) {
      \$q->localized();
    });
  }\n";
  $code.= "  public static function getResource() {
    return op_schema()->name_to_res['{$res->name}'];
  }\n";

  foreach ($res->fields as $f) {
    if ($f->type == 'relation') {
      $rel_class = op_snake_to_camel($f->rel_res->name);
      $code.= "  function {$f->name}() {\n";
      $code.= "    return \$this->belongsToMany($rel_class::class, \\{$extends}Meta::class, '{$extends_lc}_id', 'meta_value')";
      $code.= "    ->wherePivot('meta_key', 'oprel_{$f->name}')\n";
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


function op_file_url(object $file, int $w = null, int $h = null, bool $contain = null, bool $inline = false) {
  $path = op_file_path($file->token);

  $pi = pathinfo($file->name);
  $filename = $pi['filename'];

  // Build On Page filename
  $op_name = "$file->token";
  $is_thumb = $w || $h;
  if (!$is_thumb) {
    $ext = $pi['extension'] == 'php' ? 'txt' : $pi['extension'];
    $op_name.= '.' . $ext;
    $filename.= '.'. $ext;
  } else {
    $op_name.= '.'.implode('x', [$w ?: '', $h ?: '']);
    if ($contain) {
      $op_name.= '-contain';
    }

    $ext = op_preferred_image_format();
    $op_name.= ".$ext";
    $filename.= ".$ext";
  }

  $op_url = op_http_file_url($op_name,$filename,$inline);

  // Serve original files directly from On Page servers
  if (!$is_thumb) {
    return $op_url;
  }

  // Save thumbnails to the local storage
  $target_folder = op_file_path("/cache/$op_name");
  $target_path = "$target_folder/$filename";
  if (!is_file($target_path)) {
    if (!is_dir($target_folder)) mkdir($target_folder, 0775, true);
    op_download_file($op_url, $target_path);
  }

  return op_link($target_path);
}

function op_import_log_path() {
  $path = op_file_path('import-log.txt');
  if (!is_file($path)) touch($path);
  return $path;
}

function op_preferred_image_format() {
  return defined('OP_THUMBNAIL_FORMAT') ? OP_THUMBNAIL_FORMAT : 'png';
}

function op_http_file_url(string $token, string $name = null, bool $inline = null) {
  $params = array_filter([
    'name' => $name,
    'inline' => $inline,
  ], function($x) { return !is_null($x); });
  $query_string = empty($params) ? '' : '?'.http_build_query($params);
  return 'https://'.op_getopt('company').'.onpage.it/api/storage/'.$token.$query_string;
}


function op_list_files(bool $return_map = false) : array {
  if (defined('OP_DISABLE_ORIGINAL_FILE_IMPORT')) return [];
  $files = [];
  foreach (op_schema()->resources as $res) {
    $class = op_name_to_class($res->name);
    $class = $class::$meta_class;
    $meta_col = $class::$relation_field;
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
    ->pluck('meta_value', $meta_col)
    ->map(function($el) {
      return @json_decode($el);
    })
    ->filter(function($x) { return $x && @$x->token; })
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
function op_download_file(string $url, string $final_path) : int {
  $tmp_path = sys_get_temp_dir()."/".rand(1000000, 9999999);
  set_time_limit(0);
  $max_tries = 5;
  while (true) {
    $fp = fopen($tmp_path, 'w');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60*10);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_errno($ch);
    curl_close($ch);
    fclose($fp);
    if (!in_array($code, [200, 201]) || $err) {
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
function op_import_file(object $file) {
  $token = $file->info->token;
  $final_path = op_file_path($token);
  $url = op_endpoint()."/storage/$token";
  return op_download_file($url, $final_path);
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
  $ok = op_download_file($source, $zip_path);
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
  op_download_file($path, $file);


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
  $ret = $name ? @$req->$name ?: @$_REQUEST[$name] : $req;
  if ($ret === 'false') $ret = false;
  return $ret;
}

function op_locale($set = null) {
  static $locale = null;
  if ($set) {
    $locale = $set;
  }
  if (!$locale) {
    $locale = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : get_locale();
  }
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
  $key = "op_{$field->name}";
  if ($field->is_translatable) {
    if (!$lang) $lang = op_locale();
    $lang = op_locale_to_lang($lang);
    $key.= "_$lang";
  }
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
        ->withAnyStatus()
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
      op_delete_taxonomies_and_terms($taxonomies);
    }

    OpLib\Thing::query()->delete();

  } catch (\Throwable $e) {
    op_err("Something went wrong: {$e->getMessage()}", [
      'exception' => $e,
    ]);
  }
}

function op_delete_taxonomies_and_terms($taxonomies) {
  if (op_wpml_enabled()) {
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

function op_set_product_featured_image(OpLib\File $op_file, int $post_id  ){
  static $attach_to_id = null;
  static $postid_to_attach_id = null;
  static $full_folder = null;
  static $custom_folder_name = 'onpage';
  if (is_null($attach_to_id)) {
    $full_folder = wp_upload_dir()['basedir']."/$custom_folder_name";  
    wp_mkdir_p($full_folder);
    $attach_to_id = DB::table('posts')
      ->where('post_type', 'attachment')
      ->where('post_title', 'like', 'op_image_%')
      ->pluck('ID', 'post_title')
      ->all();
    $postid_to_attach_id = DB::table('postmeta')
      ->where('meta_key', '=', '_wp_attached_file')
      ->where('meta_value', 'like', "$custom_folder_name/op_image_%")
      ->pluck('post_id', 'meta_value')
      ->all();
  }

  $full_token = basename($op_file->token).'.1980x.'.op_preferred_image_format();
  $attach_key = "op_image_$full_token";

  $file = $full_folder . '/' . $attach_key;

  if (!isset($attach_to_id[$attach_key])) {
    if (!is_file($file)) {
      echo "importing...\n";
      op_download_file(op_http_file_url($full_token), $file);
    }

    $wp_filetype = wp_check_filetype($attach_key, null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => $attach_key,
        'post_content' => '',
        'post_status' => 'open',
    );

    // op_record('insert attachemnt...');
    $attach_id = wp_insert_attachment( $attachment, $file );
    
    // op_record('setting post attachemnt...');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    wp_update_attachment_metadata( $attach_id, $attach_data );
    
    $attach_to_id[$attach_key] = $attach_id;
    
  }
  $attach_id = $attach_to_id[$attach_key];
  // op_record('set thumb '.$attach_id."   ".$post_id);
  set_post_thumbnail( $post_id, $attach_id );
  // op_record('done...');
}