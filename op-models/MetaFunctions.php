<?php
namespace OpLib;
use \WeDevs\ORM\Eloquent\Facades\DB;

if (!defined('OP_PLUGIN')) die(400);


trait MetaFunctions {
  static $only_reserverd = false;
  public static function boot() {
    parent::boot();
    self::addGlobalScope('op', function($q) {
      $q->owned();
    });
    self::addGlobalScope('opmeta', function($q) {
      $q->loaded(self::$only_reserverd);
    });
  }

  static function scopeUnfiltered($q) {
    $q->withoutGlobalScope('op');
  }
  static function scopeWhereMeta($q, string $key, $operator, $value = null) {
    if (is_null($value)) {
      $value = $operator;
      $operator = '=';
    }
    if (is_array($value) && $operator != '=') {
      throw new Exception("Cannot apply operator $operator for array value");
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
  static function scopeIsOutdated($q, $timestamp) {
    $q->whereHas('meta', function($q) use ($timestamp) {
      $q->where('meta_key', 'op_imported_at*');
      $q->where('meta_value', $timestamp);
    }, 0);
  }
  static function scopeOwned($q) {
    $q->whereHas('meta', function($q) {
      $q->where('meta_key', 'op_id*');
    });
    $q->whereHas('meta', function($q) {
      $q->where('meta_key', 'op_res*');
    });
    $q->whereHas('meta', function($q) {
      $q->where('meta_key', 'op_lang*');
    });
  }
  function scopeWhereWordpressId($q, $param) {
    if (is_array($param)) {
      $q->whereIn("{$this->getTable()}.{$this->primaryKey}", $param);
    } else {
      $q->where("{$this->getTable()}.{$this->primaryKey}", $param);
    }
  }
  function getMeta(string $key) {
    return @$this->meta->firstWhere('meta_key', $key)->meta_value;
  }
  function getId() {
    return @$this->getMeta('op_id*');
  }
  function getLang() {
    return @$this->getMeta('op_lang*');
  }
  function getResourceId() {
    return @$this->getMeta('op_res*');
  }
  function scopeLocalized($q, string $lang = null) {
    if (!$lang) {
      $lang = op_locale();
    }
    $q->whereLang($lang);
  }
  function scopeUnlocalized($q) {
    $q->withoutGlobalScope('oplang');
  }
  function scopeUnowned($q) {
    $q->withoutGlobalScope('op');
  }

  function getTableWithoutPrefix() {
    return substr($this->getTable(), count(OP_WP_PREFIX));
  }

  function slugExists($slug) {
    if ($this->is_post) {
      return Post::query()->unfiltered()->where(self::$slug_field, $slug)->exists();
    } else {
      $query = Term::query()->unfiltered()->where(self::$slug_field, $slug)
      ->whereHas('taxonomies', function($q) {
         $q->where('taxonomy', 'product_cat');
      });
      return $query->exists();
    }
  }

  function setSlug($slug) {
    if (!$slug) return null;
    $current_slug = $this->getSlug();
    
    $slug = sanitize_title_with_dashes($slug);
    if ($slug === $current_slug) return $slug;
    $slug = op_slug($slug, $this, $current_slug);
    if ($slug === $current_slug) return $slug;
    $this->update([
      self::$slug_field => $slug,
    ]);
    return $slug;
  }

  public function val($name, $lang = null) {

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

  public function getIdAttribute() {
    return $this->attributes[$this->primaryKey];
  }

  public function getResourceAttribute() {
    return op_schema()->id_to_res[$this->getResourceId()];
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
    $q->where('op_res', $res_id);
  }

  public static function scopeWhereField($q, string $name, $op, $value = null) {
    $q->whereHas('meta', function($q) use ($name, $op, $value) {
      $lang = op_locale();
      $q->where('meta_key', self::fieldToMetaKey($name, $lang))
        ->where('meta_value', $op, $value);
    });
  }
  public static function scopeWhereFieldIn($q, string $name, array $value) {
    $q->whereHas('meta', function($q) use ($name, $value) {
      $lang = op_locale();
      $q->where('meta_key', self::fieldToMetaKey($name, $lang))
        ->whereIn('meta_value', $value);
    });
  }
  public static function scopeOrWhereField($q, string $name, $op, $value = null) {
    $q->orWhereHas('meta', function($q) use ($name, $op, $value) {
      $lang = op_locale();
      $q->where('meta_key', self::fieldToMetaKey($name, $lang))
        ->where('meta_value', $op, $value);
    });
  }
  public static function scopeOrWhereFieldIn($q, string $name, array $value) {
    $q->orWhereHas('meta', function ($q) use ($name, $value) {
        $lang = op_locale();
        $q->where('meta_key', self::fieldToMetaKey($name, $lang))
        ->whereIn('meta_value', $value);
    });
  }

  public static function scopePluckField($q, $name, $lang = null) {
    $ids = $q->pluck(self::getPrimaryKey());
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
    $q->orderBy($this->is_post ? 'menu_order' : 'op_order');
  }

  public static function getPrimaryKey() {
    $t = new self();
    return $t->primaryKey;
  }

  public static function fieldToMetaKey(string $name, string $lang = null) {
    $res = self::getResource();
    $f = @$res->name_to_field[$name];
    if (!$f) {
      return null;
      // throw new \Exception("Cannot find field $name");
    }
    return op_field_to_meta_key($f, $lang);
  }

  public function permalink(string $lang = null) {
    if ($lang) {
      $id = $this->getTranslationId($lang);
    } else {
      $id = $this->id;
    }
    return $this->is_post
      ? get_permalink($id)
      : get_category_link($id);
  }

  public function getSlug() {
    return $this->attributes[self::$slug_field];
  }

  public function getTranslationId(string $lang) {
    $id = $this->id;
    if (!$this->is_post) {
      $tax = $this->taxonomies()->first();
      if (!$tax) {
        throw new \Exception("Element $this->id has no taxonomy");
      }
      $id = $tax->term_taxonomy_id;
    }


    $original_tx = DB::table('icl_translations')
      ->where('element_type', $this->is_post ? 'post_product' : 'tax_product_cat')
      ->where('element_id', $id)
      ->first();
    if (!$original_tx) {
      throw new \Exception("Element $this->id is not translated");
    }
    $tx = DB::table('icl_translations')
      ->where('language_code', $lang)
      ->where('trid', $original_tx->trid)
      ->first();
    if (!$original_tx) {
      throw new \Exception("Element $this->id is not translated into $lang");
    }

    if ($this->is_post) {
      return $tx->element_id;
    } else {
      $tax = DB::table('term_taxonomy')
        ->where('term_taxonomy_id', $tx->element_id)
        ->first();
      if (!$tax) {
        throw new \Exception("Cannot find taxonomy {$tx->element_id}");
      }
      return $tax->term_id;
    }
  }
}
