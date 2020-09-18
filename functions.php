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
define('PFX', $wpdb->prefix);


function op_slug(string $base, string $table, string $field, string $old_slug = null) {
  $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9_]+/', '-', $base)));
  $suffix = '';
  while ($old_slug != $base_slug.$suffix && DB::table($table)->where($field, $base_slug.$suffix)->exists()) {
    if (!$suffix) $suffix = 2;
    else $suffix++;
  }
  return $base_slug.$suffix;
}

function op_download_json($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  $result = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return $status == 200 && $result ? json_decode($result) : null;
}

function op_initdb() {

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
    @DB::statement("ALTER TABLE `".PFX."posts` ADD COLUMN `op_res` bigint unsigned NULL;");
    @DB::statement("ALTER TABLE `".PFX."posts` ADD COLUMN `op_id` bigint unsigned NULL;");


    @DB::statement("ALTER TABLE `".PFX."terms` ADD COLUMN `op_res` bigint unsigned NULL;");
    @DB::statement("ALTER TABLE `".PFX."terms` ADD COLUMN `op_id` bigint unsigned NULL;");

    @DB::statement("ALTER TABLE `".PFX."posts`
                    ADD UNIQUE `op_res_op_id` (`op_res`, `op_id`),
                    ADD INDEX `op_res` (`op_res`),
                    ADD UNIQUE `op_id` (`op_id`)");
    @DB::statement("ALTER TABLE `".PFX."terms`
                    ADD UNIQUE `op_res_op_id` (`op_res`, `op_id`),
                    ADD INDEX `op_res` (`op_res`),
                    ADD UNIQUE `op_id` (`op_id`)");

    @DB::statement("ALTER TABLE `".PFX."posts` ADD COLUMN `op_dirty` BOOL;");
    @DB::statement("ALTER TABLE `".PFX."terms` ADD COLUMN `op_dirty` BOOL;");

    op_setopt('migration', 33);
    DB::statement("SET sql_mode = '$orig_mode'");
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
  if ($settings) {
    $opts = [];
    foreach ((array) $settings as $key => $value) {
      $opts[] = [
        'option_name' => "on-page-$key",
        'option_value' => json_encode($value),
      ];
    }
    DB::table('options')->where('option_name', 'like', 'on-page-%')->delete();
    DB::table('options')->insert($opts);
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

function op_extract_schema(object $db = null) {
  if (!$db) $db = op_download_snapshot();
  else $db = clone $db;
  $res_map = [];
  foreach ($db->resources as &$res) {
    $res = clone ($res);
    unset($res->data);
    $field_map = [];
    foreach ($res->fields as $f) $field_map[$f->name] = $f;
    $res->fields = $field_map;
    $res_map[$res->name] = $res;
  }
  $db->resources = $res_map;
  return $db;
}

function op_schema(object $set = null) {
  static $schema = null;
  if ($set) {
    op_setopt('schema', $set);
    $schema = null;
  }
  if (!$schema) {
    $schema = op_getopt('schema');
    foreach ($schema->resources as $res) {
      $schema->id_to_res[$res->id] = $res;
      foreach ($res->fields as &$field) {
        $schema->id_to_field[$field->id] = $field;
      }
    }
    foreach ($schema->resources as $res) {
      foreach ($res->fields as &$field) {
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
  $sElapsedSecs = str_pad(number_format($tElapsedSecs, 3), 8, " ", STR_PAD_LEFT);
  $sElapsedSecsQ = str_pad(number_format($tElapsedSecsQ, 3), 8, " ", STR_PAD_LEFT);
  $tStartQ = $tS;
  $steps[] = "$sElapsedSecs $sElapsedSecsQ  $label";
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
    $ret.= strtoupper($s[0]).substr($s, 1);
  }
  return $ret;
}

function op_import_snapshot(object $sett = null) {
  if (!is_dir(op_file_path('/'))) {
    mkdir(op_file_path('/'));
  }
  if (!is_dir(op_file_path('cache'))) {
    mkdir(op_file_path('cache'));
  }
  if (!is_dir(__DIR__.'/db-models')) {
    mkdir(__DIR__.'/db-models');
  }

  $db = op_download_snapshot($sett);
  op_record('download completed');
  $schema = op_schema(op_extract_schema($db));

  // DB::beginTransaction();

  // Mark everything as old
  DB::table("posts")->whereNotNull('op_id')->update(['op_dirty' => true]);
  DB::table("terms")->whereNotNull('op_id')->update(['op_dirty' => true]);

  // Order resources
  $res_map = [];
  foreach ($db->resources as $res) $res_map[$res->id] = $res;
  $res_deps = [];
  foreach ($res_map as $res) {
    $res_deps[$res->id] = collect($res->fields)
      ->where('rel_type', 'dst')
      ->pluck('rel_res_id')
      ->diff([$res->id]);
  }

  $imported_map = [];
  $next_res = function() use ($res_map, $res_deps, &$imported_map) {
    foreach ($res_deps as $id => $deps) {
      if (isset($imported_map[$id])) continue;
      $deps_satisfied = true;
      foreach ($deps as $dep_id) {
        if (!isset($imported_map[$dep_id])) {
          $deps_satisfied = false;
          break;
        }
      }
      if (!$deps_satisfied) continue;
      $imported_map[$id] = true;
      return $res_map[$id];
    }
  };


  while ($res = $next_res()) {
    op_import_resource($db, $res, $res_map);
    op_record("fine $res->label");
  }
  delete_option("product_cat_children");
  op_record('import ended');

  // Delete old data
  DB::table("posts")->whereNotNull('op_id')->where('op_dirty', true)->delete();
  DB::table("terms")->whereNotNull('op_id')->where('op_dirty', true)->delete();

  op_record('deleted old data');


  $old_models = glob(__DIR__.'/db-models/*.php');
  foreach ($old_models as $path) unlink ($path);
  foreach ($schema->resources as $res) op_gen_model($schema, $res);
  op_record('created models');

  flush_rewrite_rules();
  op_record('permalinks flushed');

  // DB::commit();
  op_record('transaction committed');
}

function op_import_resource(object $db, object $res, array $res_map) {
  $lab = collect($res->fields)->whereNotIn('type', ['relation', 'file', 'image'])->first();
  $lab_field = $lab->id.($lab->is_translatable ? "_{$db->langs[0]}" : '');
  $lab_img = collect($res->fields)->where('type', 'image')->first();
  $lab_img_field = $lab_img->id.($lab_img->is_translatable ? "_{$db->langs[0]}" : '');

  $base_table = $res->is_product ? 'posts' : 'terms';
  $base_table_key = $res->is_product ? 'ID' : 'term_id';
  $base_table_slug = $res->is_product ? 'post_name' : 'slug';
  $base_tablemeta = $res->is_product ? 'postmeta' : 'termmeta';
  $base_tablemeta_ref = $res->is_product ? 'post_id' : 'term_id';

  // Create map of resource fields [id => field]
  $field_map = [];
  foreach ($res->fields as $f) $field_map[$f->id] = $f;
  op_record('mapped $field_map');

  // Start inserting
  $object_ids = [];
  $all_meta = [];
  $current_objects = [];
  foreach (DB::table($base_table)->where('op_res', $res->id)->get() as $x) {
    $current_objects[$x->op_id] = $x;
  }
  $current_taxonomies = [];
  foreach (DB::table('term_taxonomy')->where('taxonomy', 'product_cat')->get() as $x) {
    $current_taxonomies[$x->term_id] = $x;
  }
  op_record('mapped $current_objects');

  foreach ($res->data as $thing_i => $thing) {
    if (!@$thing->fields->$lab_field) continue;
    $log = "$res->name $thing->id ".memory_get_usage();
    // file_put_contents(__DIR__.'/log.txt', "$log\n", FILE_APPEND);
    // Commit every X elements for speed (we like speed)
    if (($thing_i+1) % 10 == 0) {
      // DB::commit();
      // DB::beginTransaction();
    }


    // Look for the object if it exists already
    $object = @$current_objects[$thing->id];

    // Prepare data
    $data = !$res->is_product ? [
      'op_res' => $res->id,
      'op_id' => $thing->id,
      'op_dirty' => false,
      'name' => $thing->fields->$lab_field,
      'slug' => $object ? $object->slug : op_slug($thing->fields->$lab_field, 'terms', 'slug', @$object->slug),
      'term_group' => 0,
    ] : [
      'op_res' => $res->id,
      'op_id' => $thing->id,
      'op_dirty' => false,
      'post_author' => 1,
      'post_date' => @$thing->created_at ?: date('Y-m-d H:i:s'),
      'post_date_gmt' => @$thing->created_at ?: date('Y-m-d H:i:s'),
      'post_content' => '',
      'post_title' => $thing->fields->$lab_field,
      'post_excerpt' => '',
      'post_status' => 'publish',
      'comment_status' => 'closed',
      'ping_status' => 'closed',
      'post_password' => '',
      'post_name' => $object ? $object->post_name : op_slug($thing->fields->$lab_field, 'posts', 'post_name', @$object->post_name),
      'to_ping' => '',
      'pinged' => '',
      'post_modified' => @$thing->updated_at ?: date('Y-m-d H:i:s'),
      'post_modified_gmt' => @$thing->updated_at ?: date('Y-m-d H:i:s'),
      'post_content_filtered' => '',
      'post_parent' => 0,
      'guid' => '',
      'menu_order' => $thing_i,
      'post_type' => 'product',
      'post_mime_type' => '',
      'comment_count' => 0,
    ];


    // Create or update the object
    if ($object) {
      $object_id = $object->$base_table_key;
      DB::table($base_table)->where('op_id', $thing->id)->update($data);
    } else {
      $object_id = DB::table($base_table)->insertGetId($data);
    }
    $object_ids[$thing->id] = $object_id;

    // Calculate new metadata
    $meta = [];
    $meta[] = [
      $base_tablemeta_ref => $object_id,
      'meta_key' => 'op_res*',
      'meta_value' => $res->id,
    ];
    $meta[] = [
      $base_tablemeta_ref => $object_id,
      'meta_key' => 'op_id*',
      'meta_value' => $thing->id,
    ];

    if ($lab_img_field && $thing->fields->$lab_img_field) {
      $meta[] = [
        $base_tablemeta_ref => $object_id,
        'meta_key' => $res->is_product ? '_thumbnail_id' : 'thumbnail_id',
        'meta_value' => json_encode(
          $thing->fields->$lab_img_field,
        ),
      ];
    }


    // Fields
    foreach ($thing->fields as $name => $values) {
      $e = explode('_', $name);
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

    // Relations
    foreach ($thing->rel_ids as $fid => $ids) {
      $f = $field_map[$fid];
      foreach ($ids as $id) {
        $meta[] = [
          $base_tablemeta_ref => $object_id,
          'meta_key' => 'op_'.$f->name,
          'meta_value' => $id,
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

    // Recreate the metadata
    $all_meta = array_merge($all_meta, $meta);
    // DB::table($base_tablemeta)->where($base_tablemeta_ref, $object_id)->delete();
    // DB::table($base_tablemeta)->insert($meta);
    // unset($meta);



    // Delete all relations with parents
    if ($res->is_product) {
      DB::table('term_relationships')->where('object_id', $object_id)->delete();
    } else {
      DB::table('term_taxonomy')->where('term_id', $object_id)->delete();
    }

    // If product, set it as simple
    if ($res->is_product) {
      wp_set_object_terms($object_id, 'simple', 'product_type');
    } else {
      DB::table('term_taxonomy')->insert([[
        'term_id' => $object_id,
        'taxonomy' => 'product_cat',
        'description' => '',
        'parent' => 0,
        'count' => 1,
      ]]);
    }
  } // end $thing->data cycle

  op_record('cycle ended');
  DB::table($base_tablemeta)->whereIn($base_tablemeta_ref, $object_ids)
    ->where(function($q) {
      $q->where('meta_key', 'like', 'op\_%')
        ->orWhereIn('meta_key', [
          '_sale_price', '_regular_price', '_price',
          '_sku', '_weight', '_width', '_length', '_height',
          '_thumbnail_id',
        ]);
    })->delete();
  op_record('deleted meta');
  DB::table($base_tablemeta)->insert($all_meta);
  op_record('created meta');

  return true;
}

function op_gen_model(object $schema, object $res) {
  $camel_name = op_snake_to_camel($res->name);
  $extends = $res->is_product ? 'Post' : 'Term';
  $extends_lc = strtolower($extends);

  $code = "<?php\nnamespace Op; \n";
  $code.= "class $camel_name extends \\OpLib\\$extends {\n";
  $code.= "  public static function boot() {
    parent::boot();
    self::addGlobalScope('op', function(\$q) {
      \$q->where('op_res', $res->id)->loaded();
    });
  }\n";
  $code.= "  public static function getResource() {
    return op_schema()->resources->{$res->name};
  }\n";

  foreach ($res->fields as $f) {
    if ($f->type == 'relation') {
      $rel_method = $f->rel_res->is_product ? 'posts' : 'terms';
      $rel_class = op_snake_to_camel($f->rel_res->name);
      $code.= "  function $f->name() {\n";
      $code.= "    return \$this->belongsToMany($rel_class::class, \\OpLib\\{$extends}Meta::class, '{$extends_lc}_id', 'meta_value', null, 'op_id')\n";
      $code.= "    ->wherePivot('meta_key', 'op_$f->name');\n";
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
    if (!$w && !$h) {
      $target_path = op_file_path('/cache/'.substr($file->token, 0, 4).$file->name);
      if (!file_exists($target_path)) {
        symlink($path, $target_path);
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
          return op_link($path);
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

function op_list_files() {
  $files = [];
  foreach (op_schema()->resources as $res) {
    $class = $res->is_product ? 'OpLib\PostMeta' : 'OpLib\TermMeta';
    $meta_col = $res->is_product ? 'post_id' : 'term_id';
    $res_files_query = $class::whereHas('parent', function($q) use ($res) {
      $q->where('op_res', $res->id);
    });

    $res_files_query->where(function($q) use ($res) {
      foreach (collect($res->fields)->whereIn('type', ['file', 'image']) as $field) {
        if (!$field->is_translatable) {
          $q->orWhere('meta_key', op_field_to_meta_key($field));
        } else {
          foreach (op_schema()->langs as $lang) {
            $q->orWhere('meta_key', op_field_to_meta_key($field, $lang));
          }
        }
      }
    });

    $res_files = $res_files_query->get()
    ->pluck('meta_value')
    ->map(function($el) {
      return json_decode($el);
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
      $files->$meta_col[] = $object_id;
    }
  }
  foreach ($files as &$f) {
    $f->is_imported = is_file(op_file_path($f->info->token));
  }
  return array_values($files);
}

function op_file_path($token) {
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
  $path = op_file_path($token);
  $url = $url = op_endpoint()."/storage/$token";
  $bytes = copy($url, $path);
  $ret = [
    'url' => $url,
    'path' => $path,
    'bytes' => $bytes,
  ];
  return $bytes;
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
  $source = 'https://github.com/gufoe/woocommerce-onpage/raw/master/woocommerce-onpage.zip';
  $ok = copy($source, $zip_path);
  if (!$ok) op_err('Cannot download update from github');
  require_once(ABSPATH .'/wp-admin/includes/file.php');
  WP_Filesystem();
  unzip_file($zip_path, __DIR__);
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
  return $name ? @$req->$name : $req;
}

function op_locale($set = null) {
  static $locale = null;
  if (!$locale || $set) $locale = $set ?: substr(get_locale(), 0, 2);
  return $locale;
}

function op_category($key, $value) {
  $term = OpLib\Term::where($key, $value)->first();
  if (!$term) return null;

  $class = 'Op\\'.op_snake_to_camel($term->resource->name);
  $model = new $class($term->getAttributes());
  $model->setRelation('meta', $term->meta);
  return $model;
}

function op_product($key, $value) {
  $term = OpLib\Post::where($key, $value)->first();
  if (!$term) return null;

  $class = 'Op\\'.op_snake_to_camel($term->resource->name);
  $model = new $class($term->getAttributes());
  $model->setRelation('meta', $term->meta);
  return $model;
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

  $field = $res->fields->$field_name;
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
