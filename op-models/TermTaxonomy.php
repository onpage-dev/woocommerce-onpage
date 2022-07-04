<?php

namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

class TermTaxonomy extends Model
{
  protected $table = OP_WP_PREFIX . 'term_taxonomy';
  protected $guarded = [];
  public $timestamps = false;
  protected $primaryKey = 'term_taxonomy_id';

  public static function boot()
  {
    parent::boot();
    self::addGlobalScope('_op-cat-product_cat', function ($q) {
      $q->where('taxonomy', 'product_cat');
    });
  }

  public function term()
  {
    return $this->belongsTo(Term::class, 'term_id', 'term_id');
  }
  public function icl_translation()
  {
    return $this->hasOne(IclTranslation::class, 'element_id', 'term_id')->where('element_type', 'tax_product_cat');
  }

  function getIdAttribute()
  {
    return $this->attributes[$this->primaryKey];
  }
}
