<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);


trait MetaFunctions {
  public static function boot() {
    parent::boot();
    self::addGlobalScope('op', function($q) {
      $q->whereNotNull('op_id')->loaded();
    });
  }

  public function val($name, $lang = null) {
    $field = @$this->resource->fields->$name;
    if (!$field) return;
    $meta_key = op_field_to_meta_key($field, $lang);
    if (!$meta_key) return null;
    $values = @$this->meta->where('meta_key', $meta_key)->pluck('meta_value');
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
    if (!is_int($res_id)) {
      $res_id = op_schema()->resources->$res_id->id;
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
      $fields = array_keys((array)self::getResource()->fields);
    }
    $string = str_replace('%', '\\%', $string);
    $string = str_replace('_', '\\_', $string);
    $q->where(function($q) use ($fields, $string) {
      foreach ($fields as $field_name) {
        $q->orWhereField($field_name, 'like', "%$string%");
      }
    });
  }

  public static function getPrimaryKey() {
    $t = new self();
    return $t->primaryKey;
  }

  public static function fieldToMetaKey(string $name, string $lang = null) {
    $res = self::getResource();
    $f = @$res->fields->$name;
    if (!$f) {
      return null;
      // throw new \Exception("Cannot find field $name");
    }
    return op_field_to_meta_key($f, $lang);
  }

  public function link() {
    return op_link_to($this);
  }

  public function permalink() {
    return $this->is_post
      ? get_permalink($this->id)
      : get_category_link($this->id);
  }

  public function getSlug() {
    return $this->attributes[self::$slug_field];
  }
}
