<?php
namespace OpLib;
use \WeDevs\ORM\Eloquent\Facades\DB;

if (!defined('OP_PLUGIN')) die(400);


trait MetaFunctions {
  public static function boot() {
    parent::boot();
    self::addGlobalScope('op', function($q) {
      $q->whereNotNull('op_id')->loaded();
    });
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
    return op_schema()->id_to_res[$this->op_res];
  }

  public function meta() {
    return $this->hasMany(self::$meta_class, self::$meta_ref);
  }

  public static function scopeLoaded($q) {
    $q->with('meta');
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
      $q->where('meta_key', self::fieldToMetaKey($name))
        ->where('meta_value', $op, $value);
    });
  }
  public static function scopeOrWhereField($q, string $name, $op, $value = null) {
    $q->orWhereHas('meta', function($q) use ($name, $op, $value) {
      $lang = op_locale();
      $q->where('meta_key', self::fieldToMetaKey($name))
        ->where('meta_value', $op, $value);
    });
  }

  public static function scopePluckField($q, $name, $lang = null) {
    $ids = $q->pluck(self::getPrimaryKey());
    return self::$meta_class::whereIn(self::$meta_ref, $ids)
      ->where('meta_key', self::fieldToMetaKey($name, $lang))
      ->pluck('meta_value')
      ->all();
  }

  public static function scopeSearch($q, $string, array $fields = []) {
    if (empty($fields)) {
      $fields = array_keys(self::getResource()->name_to_field);
    }
    $string = str_replace('%', '\\%', $string);
    $string = str_replace('_', '\\_', $string);
    $q->where(function($q) use ($fields, $string) {
      foreach ($fields as $field_name) {
        $q->orWhereField($field_name, 'like', "%$string%");
      }
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
