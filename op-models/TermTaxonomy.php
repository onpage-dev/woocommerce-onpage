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
  }

  public function term()
  {
    return $this->belongsTo(Term::class, 'term_id', 'term_id');
  }
  public function icl_translation()
  {
    $element_type = method_exists(static::class, 'getResource')
      ? op_resource_target_wpml_element_type(static::getResource())
      : 'tax_product_cat';
    return $this->hasOne(IclTranslation::class, 'element_id', 'term_taxonomy_id')->where('element_type', $element_type);
  }

  function getIdAttribute()
  {
    return $this->attributes[$this->primaryKey];
  }
}
