<?php
namespace OpLib;
use \WeDevs\ORM\Eloquent\Facades\DB;

if (!defined('OP_PLUGIN')) die(400);


trait MetaFunctions {
  static $only_reserverd = false;
  public static function metaBoot() {
    // Only get terms and products which have On Page metadata
    self::addGlobalScope('_op-owned', function($q) {
      $q->owned();
    });

    // Load metadata (field values)
    self::addGlobalScope('_op-loadmeta', function($q) {
      $q->loaded(self::$only_reserverd);
    });
  }

  static function addGlobalScope($name,  $filter = NULL) {
    if (!is_string($name)) {
      throw new \Exception("Invalid scope name, must be string");
    }
    if (!is_callable($filter)) {
      throw new \Exception("Invalid scope filter for $name, must be callable");
    }
    $is_reserved = strpos($name, '_op-')===0;
    parent::addGlobalScope($name, function($query) use ($is_reserved, $filter) {
      if ($is_reserved || !op_ignore_user_scopes()) {
        $filter($query);
      }
    });
  }

  static function scopeUnfiltered($q) {
    $q->withoutGlobalScope('_op-owned');
  }
  static function scopeWhereMeta($q, string $key, $operator, $value = null) {
    if (is_null($value)) {
      $value = $operator;
      $operator = '=';
    }
    if (is_array($value) && $operator != '=') {
      throw new \Exception("Cannot apply operator $operator for array value");
    }
    if (static::op_type == 'thing' && strpos($key, '*') !== false) {
      $real_field = null;
      if ($key == 'op_id*') $real_field = 'id';
      else if ($key == 'op_res*') $real_field = 'resource_id';
      if ($real_field) {
        $q->where($real_field, $operator, $value);
      }
      return;
    }
    $q->whereHas('meta', function($q) use ($key, $operator, $value) {
      $q->where('meta_key', $key);
      if (is_array($value)) {
        $q->whereIn('meta_value', $value);
      } else {
        $q->where('meta_value', $operator, $value);
      }
    });
  }
  static function scopeWhereLang($q, $op, $val = null) {
    $q->whereMeta('op_lang*', $op, $val);
  }
  static function scopeWhereId($q, $op, $val = null) {
    $q->whereMeta('op_id*', $op, $val);
  }
  static function scopeWhereRes($q, $op, $val = null) {
    $q->whereMeta('op_res*', $op, $val);
  }

  static function isThing() {
    return static::op_type == 'thing';
  }
  static function isPost() {
    return static::op_type == 'post';
  }
  static function isTerm() {
    return static::op_type == 'term';
  }
  static function scopeOwned($q) {
    if (self::isThing()) {
      if (method_exists(static::class, 'getResource')) {
        $q->where('resource_id', self::getResource()->id);
      }
      return;
    }


    // These two checks are reduntant and have been disabled for performance
    // on date 20220-05-11
    // $q->whereHas('meta', function($q) { $q->where('meta_key', 'op_id*'); });
    // $q->whereHas('meta', function($q) { $q->where('meta_key', 'op_lang*'); });

    // This has been chosen as the proper way to check if something
    // is owned by this plugin
    $q->whereHas('meta', function($q) {
      $q->where('meta_key', 'op_res*');
      if (method_exists(static::class, 'getResource')) {
        $q->where('meta_value', self::getResource()->id);
      }
    });
  }
  function scopeWhereWordpressId($q, $param) {
    if (self::isThing()) return $q->whereRaw('false');

    if (is_array($param)) {
      $q->whereIn("{$this->getTable()}.{$this->primaryKey}", $param);
    } else {
      $q->where("{$this->getTable()}.{$this->primaryKey}", $param);
    }
  }
  function getMeta(string $key) {
    $meta = $this->meta->firstWhere('meta_key', $key);
    if ($meta) return $meta->meta_value;
  }
  function getId() {
    return $this->getMeta('op_id*');
  }
  function getLang() {
    return $this->getMeta('op_lang*');
  }
  function getResourceId() {
    return $this->getMeta('op_res*');
  }
  function getDefaultFolderId() {
    return $this->getMeta('op_default_folder_id*');
  }
  function scopeLocalized($q, string $lang = null) {
    if (self::isThing()) return;
    if (!$lang) {
      $lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : op_locale();
    }
    $q->whereLang($lang);
  }
  function scopeUnlocalized($q) {
    $q->withoutGlobalScope('_op-lang');
  }
  function scopeUnowned($q) {
    $q->withoutGlobalScope('_op-owned');
  }

  function getTableWithoutPrefix() {
    return substr($this->getTable(), strlen(OP_WP_PREFIX));
  }

  function slugExists($slug) {
    if (self::isThing()) return false;
    if ($this->is_post) {
      $query = Post::query()->withoutGlobalScopes()->where(self::$slug_field, $slug);
      if (op_wpml_enabled()) {
        $query->whereHas('icl_translation', function($q) {
          $q->where('language_code', op_locale());
        });
      }
      return $query->exists();
    } else {
      $query = Term::query()->unfiltered()->where(self::$slug_field, $slug)
      ->whereHas('taxonomies', function($q) {
         if (op_wpml_enabled()) {
           $q->whereHas('icl_translation', function($q) {
             $q->where('language_code', op_locale());
           });
         }
      });
      return $query->exists();
    }
  }

  function setSlug($slug) {
    if (self::isThing()) return false;
    if (!$slug) return null;
    $current_slug = $this->getSlug();

    $slug = op_slug($slug, $this, $current_slug);
    if ($slug === $current_slug) return $slug;
    $this->update([
      self::$slug_field => $slug,
    ]);
    return $slug;
  }

  public function val($name, $lang = null) {
	  $path = explode('.', $name);
	  $name = array_pop($path);
	  if (count($path)) {
		  $relation = array_shift($path);
		  $items = $this->$relation;
		  if (is_null($items)) throw new \Exception("Relation not found: {$this->resource->label}.$relation");
		  $item = $items->first();
		  if (!$item) return null;
		  $path[] = $name;
		  return $item->val(implode('.', $path));
	  }

    $field = @$this->resource->name_to_field[$name];
    if (!$field) return;
    $meta_key = op_field_to_meta_key($field, $lang);
    if (!$meta_key) return null;
    $values = @$this->meta->where('meta_key', $meta_key)->pluck('meta_value');
    if ($field->type == 'dim1' || $field->type == 'dim2' || $field->type == 'dim3') {
      $values = $values->map('json_decode');
    }
    return $field->is_multiple ? $values->all() : $values->first();
  }

  function getValues(string $lang = null) {
    $ret = [];
    foreach ($this->resource->fields as $field) {
      if ($field->type == 'relation') {
        $ret[$field->name] = $this->{$field->name}->pluck('id')->all();
      } else {
        $ret[$field->name] = $this->val($field->name, $lang);
      }
    }
    return $ret;
  }

  public function escval($name, $lang = null) {
    $ret = $this->val($name, $lang);
    if (is_string($ret)) {
      $ret = op_e($ret);
    }
    return $ret;
  }

  public function file($name, $lang = null) {
    $value = $this->val($name, $lang);
    if (is_null($value)) return;

    $_m = is_array($value);
    if (!$_m) $value = [$value];
    $value = array_map(function($json) {
      $v = @json_decode($json);
      if (!$v) throw new \Exception("cannot parse $json");
      return new File($v);
    }, $value);
    return $_m ? $value : @$value[0];
  }

  public function getIdAttribute() : int {
    return $this->attributes[$this->primaryKey];
  }

  public function getResourceAttribute() {
    return op_schema()->id_to_res[$this->getResourceId()];
  }
  public function getField(string $field_name) : object {
    return $this->resource->name_to_field($field_name);
  }
  public function getFieldLabel(string $field_name, string $lang = null) {
    return op_label($this->getField($field_name), $lang);
  }
  public function getFieldUnit(string $field_name, string $lang = null) {
    return $this->getField($field_name)->unit;
  }
  public function getResourceLabel(string $field_name, string $lang = null) {
    return op_label($this->getLabel(), $lang);
  }

  public function scopeDeepWhere($q, $path, callable $fn, bool $is_or = false) {
    if (is_string($path)) $path = explode('.', $path);
    if (empty($path)) {
        return $fn($q);
    }
    $field = array_shift($path);
    return $q->{$is_or ? 'orWhereHas' : 'whereHas'}($field, function($q) use ($path, $fn) {
        $q->deepWhere($path, $fn);
    });
  }

  public function meta() {
    return $this->hasMany(self::$meta_class, self::$meta_ref);
  }

  public static function scopeLoaded($q, bool $only_reserverd = false) {
    $q->with(['meta' => function($q) use($only_reserverd) {
      $q->orderBy('meta_id', 'asc');
      if ($only_reserverd) {
        $q->where('meta_key', 'like', 'op\\_%*');
      }
    }]);
  }

  public static function scopeRes($q, $res_id) {
    if (!preg_match('/^\d+$/', $res_id)) {
      $res_id = op_schema()->name_to_res[$res_id]->id;
    }
    $q->whereMeta('op_res*', $res_id);
  }

  public static function scopeWhereField($q, string $name, $op, $value = null, bool $is_or = false) {
    if (is_null($value) && !in_array("$op", ['=', '<>'])) {
      $value = $op;
      $op = '=';
    }
    $path = explode('.', $name);
    if (count($path) > 1) {
      $field_name = array_pop($path); // remove last piece from path (eg. we get "name" from "categories.products.name")
      $q->{$is_or ? 'orDeepWhere' : 'deepWhere'}($path, function($q) use ($field_name, $op, $value) {
        $q->whereField($field_name, $op, $value);
      });
    } else {
      $field_name = $path[0];
      $field_type = 'int';
      if ($field_name == '_wp_id') {
        return $q->where($q->qualifyColumn(self::getPrimaryKey()), $op, $value);
      }
      if ($field_name != '_id') {
        $field = self::fieldByName($field_name);
        if (!$field) {
          return;
        }
        $field_type = $field->type;
      }

      // Correct clause
      $must_exist = true;
      if ($field_type == 'bool' && $op == '=' && !$value) {
        $must_exist = false;
        $value = true;
      }
      if ($op == 'not in') {
        $op = 'in';
        $must_exist = false;
      }

      $method = $must_exist
        ? ($is_or ? 'orWhereHas' : 'whereHas')
        : ($is_or ? 'orWhereDoesntHave' : 'whereDoesntHave');
      $q->$method('meta', function($q) use ($field_name, $op, $value) {
        $lang = op_locale();
        $q->where('meta_key', self::fieldToMetaKey($field_name, $lang));
        if ($op == 'in') {
          $q->whereIn('meta_value', $value);
        } elseif ($op == 'not in') {
          $q->whereNotIn('meta_value', $value);
        } else {
          $q->where('meta_value', $op, $value);
        }
      });
    }
  }
  public static function scopeWhereFieldIn($q, string $name, array $value) {
    $q->whereField($name, 'in', $value);
  }
  public static function scopeOrWhereField($q, string $name, $op, $value = null) {
    $q->whereField($name, $op, $value, true);
  }
  public static function scopeOrWhereFieldIn($q, string $name, array $value) {
    $q->whereField($name, 'in', $value, true);
  }

  public static function scopePluckField($q, $name, $lang = null) {
    $ids = $q->pluck(self::getPrimaryKey());
    if ($ids->isEmpty()) {
      return [];
    }
    return self::$meta_class::whereIn(self::$meta_ref, $ids)
      ->where('meta_key', self::fieldToMetaKey($name, $lang))
      ->orderByRaw('FIELD ('.self::$meta_ref.', ' . $ids->implode(',') . ') ASC')
      ->pluck('meta_value')
      ->all();
  }

  public static function scopeSearch($q, $string, array $fields = [], string $lang = null) {
    if (empty($fields)) {
      $fields = array_keys(self::getResource()->name_to_field);
    }
    $string = str_replace('%', '\\%', $string);
    $string = str_replace('_', '\\_', $string);
    if (!$lang) {
      $lang = op_locale();
    }
    $q->whereHas('meta', function($q) use ($fields, $string, $lang) {
      $q->where(function($q) use ($fields, $string, $lang) {
        foreach ($fields as $field_name) {
          $q->orWhere(function($q) use ($field_name, $string, $lang) {
            $q->where('meta_key', self::fieldToMetaKey($field_name, $lang));
            $q->where('meta_value', 'like', "%$string%");
          });
        }
      });
    });
  }

  public function scopeSorted($q) {
    $q->orderBy(self::isPost() ? 'menu_order' : 'op_order');
  }

  public static function getPrimaryKey() {
    $t = new self();
    return $t->primaryKey;
  }

  public static function fieldByName(string $name) :? object {
    $res = self::getResource();
    return $res->name_to_field[$name] ?? null;
  }

  public static function fieldToMetaKey(string $name, string $lang = null) {
    if ($name === '_id') {
      return 'op_id*';
    }
    if ($name === '_res') {
      return 'op_res*';
    }
    $f = self::fieldByName($name);
    if (!$f) {
      $res = self::getResource();
      trigger_error("Cannot find field $name in $res->name", E_USER_WARNING);
      return null;
    }
    return op_field_to_meta_key($f, $lang);
  }

  public function permalink(string $lang = null) {
    if (self::isThing()) return null;

    $permalink = $this->is_post
      ? get_permalink($this->id)
      : get_term_link($this->id, 'product_cat');

    if ($lang && op_wpml_enabled()) {
      $permalink = apply_filters('wpml_permalink', $permalink, $lang);
    }
    return $permalink;
  }

  public function getSlug() {
    if (self::isThing()) return null;
    return $this->attributes[self::$slug_field];
  }


  function getRelatedItems(string $path) {
    $path = explode('.', $path);
    $ret = collect([$this]);
    foreach ($path as $field) {
      $ret = $ret->flatMap(function($thing) use ($field) {
        return $thing->$field;
      });
    }
    return $ret;
  }
  function scopeOrderByField($q, string $field_name, string $mode = 'asc') {
    $meta_class = static::$meta_class;
    $meta_table = (new $meta_class)->getTable();
    $q->leftJoin($meta_table, $q->qualifyColumn(self::getPrimaryKey()), '=', "$meta_table.".($meta_class::$relation_field));
    $field = @static::getResource()->name_to_field[$field_name];
    if (!$field) throw new \Exception("Cannot find field $field_name");
    $q->where(function($q) use ($field) {
      $q->whereNull('meta_key');
      $q->orWhere('meta_key', op_field_to_meta_key($field));
    });
    $q->orderBy("$meta_table.meta_value", $mode);
  }

  function getDefaultFolder() {
    $id = $this->getDefaultFolderId();
    if (!$id || !isset(op_schema()->id_to_folder[$id])) return null;
    return op_schema()->id_to_folder[$id];
  }
}
