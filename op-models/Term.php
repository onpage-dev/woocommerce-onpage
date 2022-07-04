<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);


class Term extends Model {
  use MetaFunctions;

  protected $table = OP_WP_PREFIX.'terms';
  protected $guarded = [];
  public $timestamps = false;
  public $is_post = false;
  const op_type = 'term';
  public $primaryKey = 'term_id';
  public static $meta_ref = 'term_id';
  public static $meta_class = TermMeta::class;
  public static $slug_field = 'slug';

  public static function boot() {
    parent::boot();

    self::metaBoot();
  }

  public function scopeSlug($q, $slug) {
    $q->where('slug', $slug);
  }

  function taxonomies() {
    return $this->hasMany(TermTaxonomy::class, 'term_id', 'term_id');
  }
}
