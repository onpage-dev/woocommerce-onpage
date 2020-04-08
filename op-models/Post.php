<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(420);

use WeDevs\ORM\Eloquent\Model;

class Post extends Model {
    protected $table = PFX.'posts';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'ID';

    public function unsafe_val($name, $lang = null) {
      $f = $this->resource->fields->$name;
      if ($f->is_translatable) {
        $name.= '_'.($lang ?: op_schema()->langs[0]);
      }
      return @$this->meta->firstWhere('meta_key', "op_$name")->meta_value;
    }

    public function val($name, $lang = null) {
      return op_e($this->unsafe_val($name, $lang));
    }

    public function file($name, $lang = null) {
      return op_e($this->file_unsafe($name, $lang));
    }
    public function file_unsafe($name, $lang = null) {
      $img = $this->unsafe_val($name, $lang);
      if (!$img) return null;
      $img = json_decode($img);
      return op_file_url($img);
    }

    public function thumb($name, $w = null, $h = null, $crop_type = null, $lang = null) {
      return op_e($this->thumb_unsafe($name, $w, $h, $crop_type, $lang));
    }
    public function thumb_unsafe($name, $w = null, $h = null, $crop_type = null, $lang = null) {
      $img = $this->unsafe_val($name, $lang);
      if (!$img) return null;
      $img = json_decode($img);
      return op_file_url($img, $w, $h, $crop_type);
    }

    public function getResourceAttribute() {
      return op_schema()->id_to_res[$this->op_res];
    }

    public function meta() {
      return $this->hasMany(PostMeta::class, 'post_id');
    }

    public function scopeLoaded($q) {
      $q->with('meta');
    }

    public function scopeSlug($q, $slug) {
      $q->where('post_name', $slug);
    }

    public function rel($name) {
      $f = $this->resource->fields->$name;
      if (!$f) die("campo non trovato $name");
      // var_dump ($f->rel_res->id);
      if ($f->rel_res->is_product) {
        // die($this->posts($f->name)->toSql());
        return $this->posts($f->name);
      } else {
        // die($this->terms($f->name)->toSql());
        return $this->terms($f->name);
      }
      // return self::whereNotNull('term_id');
    }

    public function terms(string $name = null) {
      $q = $this->belongsToMany(Term::class, PostMeta::class, 'post_id', 'meta_value', null, 'op_id');
      if ($name) $q->wherePivot('meta_key', "op_$name");
      return $q;
    }


    public function posts(string $name = null) {
      $q = $this->belongsToMany(Post::class, PostMeta::class, 'post_id', 'meta_value', null, 'op_id');
      if ($name) $q->wherePivot('meta_key', "op_$name");
      return $q;
    }

    public function scopeRes($q, $res_id) {
      if (!is_int($res_id)) {
        $res_id = op_schema()->resources->$res_id->id;
      }
      $q->where('op_res', $res_id);
    }
}
