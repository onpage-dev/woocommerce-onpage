<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

class Thing extends Model {
  use MetaFunctions;

  protected $table = OP_WP_PREFIX.'op_things';
  protected $guarded = [];
  public $timestamps = false;
  public $is_post = false;
  const op_type = 'thing';
  public $primaryKey = 'id';
  public static $meta_ref = 'thing_id';
  public static $meta_class = ThingMeta::class;
  public static $slug_field = null;

  public static function boot() {
    parent::boot();

    self::metaBoot();
  }

  public function scopeWithStatus($q, string $status = null) {
    $this->withoutGlobalScope('_op-thing-status-publish');
    $q->where('thing_status', $status);
  }
  public function scopeWithAnyStatus($q) {
    $this->withoutGlobalScope('_op-thing-status-publish');
  }

  public function scopeSlug($q, $slug) {
    $q->where('thing_name', $slug);
  }

  public function icl_translation() {
    return $this->hasOne(IclTranslation::class, 'element_id', 'ID')->where('element_type', 'thing_product');
  }
}
