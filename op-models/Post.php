<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);


class Post extends Model {
  use MetaFunctions;

  protected $table = OP_WP_PREFIX.'posts';
  protected $guarded = [];
  public $timestamps = false;
  public $is_post = true;
  const op_type = 'post';
  public $primaryKey = 'ID';
  public static $meta_ref = 'post_id';
  public static $meta_class = PostMeta::class;
  public static $slug_field = 'post_name';

  public static function boot() {
    parent::boot();

    self::addGlobalScope('_op-post-type-product', function($q) {
      $q->where('post_type', 'product');
    });
    self::addGlobalScope('post_status_publish', function($q) {
      $q->where('post_status', 'publish');
    });

    self::metaBoot();
  }

  public function scopeWithStatus($q, string $status = null) {
    $this->withoutGlobalScope('post_status_publish');
    $q->where('post_status', $status);
  }
  public function scopeWithAnyStatus($q) {
    $this->withoutGlobalScope('post_status_publish');
  }

  public function scopeSlug($q, $slug) {
    $q->where('post_name', $slug);
  }

  public function icl_translation() {
    return $this->hasOne(IclTranslation::class, 'element_id', 'ID')->where('element_type', 'post_product');
  }
}
