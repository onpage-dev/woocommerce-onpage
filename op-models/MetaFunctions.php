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

  public function val_unsafe($name, $lang = null) {
    $f = $this->resource->fields->$name;
    if (!$f) {
      throw new \Exception("Cannot find field $name");
    }
    if ($f->is_translatable) {
      $name.= '_'.($lang ?: (op_locale() ?: op_schema()->langs[0]));
    }
    return @$this->meta->firstWhere('meta_key', "op_$name")->meta_value;
  }

  public function val($name, $lang = null) {
    return op_e($this->val_unsafe($name, $lang));
  }

  public function url($name, $lang = null) {
    return op_e($this->url_unsafe($name, $lang));
  }
  public function url_unsafe($name, $lang = null) {
    $img = $this->val_unsafe($name, $lang);
    if (!$img) return null;
    $img = json_decode($img);
    return op_file_url($img);
  }

  public function filename($name, $lang = null) {
    return op_e($this->filename_unsafe($name, $lang));
  }
  public function filename_unsafe($name, $lang = null) {
    $img = $this->val_unsafe($name, $lang);
    if (!$img) return null;
    $img = json_decode($img);
    return $img->name;
  }

  public function thumb($name, $w = null, $h = null, $crop = null, $lang = null) {
    return op_e($this->thumb_unsafe($name, $w, $h, $crop, $lang));
  }
  public function thumb_unsafe($name, $w = null, $h = null, $crop = null, $lang = null) {
    $img = $this->val_unsafe($name, $lang);
    if (!$img) return null;
    $img = json_decode($img);
    return op_file_url($img, $w, $h, $crop);
  }

  public function getIdAttribute() {
    return $this->{$this->primaryKey};
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
    $f = @self::getResource()->fields->$name;
    if (!$f) {
      throw new \Exception("Cannot find field $name");
    }
    if ($f->is_translatable) {
      $name.= '_'.($lang ?: (op_locale() ?: op_schema()->langs[0]));
    }
    return "op_$name";
  }

  public function link() {
    return op_link_to($this);
  }

  public function getSlug() {
    return $this->attributes[self::$slug_field];
  }
}
