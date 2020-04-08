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

require_once __DIR__.'/import.php';


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
  // $op_db = new DB();
  // $op_db->addConnection([
  //   'driver'    => 'mysql',
  //   'host'      => DB_HOST,
  //   'database'  => DB_NAME,
  //   'username'  => DB_USER,
  //   'password'  => DB_PASSWORD,
  //   'charset'   => DB_CHARSET,
  //   // 'collation' => DB_COLLATE,
  //   'prefix'    => '',
  // ]);
  // $op_db->setAsGlobal();

  DB::statement("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
  // try {
  // } catch (PDOException $e) {
  //   echo($e);
  //   exit;
  // };

  // Create helper columns
  if (op_settings()->migration < 33) {
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

function op_settings($settings = null) {
  static $cached_settings = null;
  if ($settings) {
    $opts = [];
    foreach ((array) $settings as $key => $value) {
      $opts[] = [
        'option_name' => "op_$key",
        'option_value' => json_encode($value),
      ];
    }
    DB::table('options')->where('option_name', 'like', 'op_%')->delete();
    DB::table('options')->insert($opts);
  } elseif ($cached_settings) {
    return $cached_settings;
  }

  $ret = (object) [];
  $opts = DB::table('options')->where('option_name', 'like', 'op_%')->pluck('option_value', 'option_name')->all();
  foreach ($opts as $key => $value) {
    $ret->{substr($key, 3)} = json_decode($value);
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

function op_ret($data) {
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
  return htmlentities($string, ENT_QUOTES);
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
    op_import($db, $res, $res_map);
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

  // DB::commit();
  op_record('transaction committed');
}


function op_import(object $db, object $res, array $res_map) {
  $lab = collect($res->fields)->whereNotIn('type', ['relation', 'file', 'image'])->first();
  $lab_field = $lab->id.($lab->is_translatable ? "_{$db->langs[0]}" : '');

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
  op_record('mapped $current_objects');

  foreach ($res->data as $thing_i => $thing) {
    if (!@$thing->fields->$lab_field) continue;
    $log = "$res->name $thing->id ".memory_get_usage();
    file_put_contents(__DIR__.'/log.txt', "$log\n", FILE_APPEND);
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


    // Fields
    foreach ($thing->fields as $name => $value) {
      $e = explode('_', $name);
      $f = $field_map[$e[0]];
      $lang = @$e[1];
      $meta[] = [
        $base_tablemeta_ref => $object_id,
        'meta_key' => 'op_'.$f->name.($lang ? "_$lang" : ''),
        'meta_value' => is_scalar($value) ? $value : json_encode($value),
      ];
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
      // do_action('edit_terms', $object_id, 'product_cat');
    }
  } // end $thing->data cycle

  op_record('cycle ended');
  DB::table($base_tablemeta)->whereIn($base_tablemeta_ref, $object_ids)->delete();
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



function op_file_url($file, $w = null, $h = null, $crop_type = null) {
  $url = 'https://'.op_getopt('company').'.onpage.it/api/storage/'.$file->token;

  if ($w || $h) {
    $url.= '.'.implode('x', [$w ?: '', $h ?: '']);
    if ($crop_type) {
      $url.= '-'.$crop_type;
    }
  }

  $url.= '.png';

  $url.= '?name='.urlencode($file->name);
  return $url;
}
