<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

use WeDevs\ORM\Eloquent\Model;

class Post extends Model {
  use MetaFunctions;

  protected $table = PFX.'posts';
  protected $guarded = [];
  public $timestamps = false;
  protected $primaryKey = 'ID';
  protected static $meta_ref = 'post_id';
  protected static $meta_class = PostMeta::class;
  protected static $slug_field = 'post_name';

  public function scopeSlug($q, $slug) {
    $q->where('post_name', $slug);
  }

}
