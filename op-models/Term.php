<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

use WeDevs\ORM\Eloquent\Model;

class Term extends Model {
  use MetaFunctions;

  protected $table = PFX.'terms';
  protected $guarded = [];
  public $timestamps = false;
  public $is_post = false;
  protected $primaryKey = 'term_id';
  protected static $meta_ref = 'term_id';
  protected static $meta_class = TermMeta::class;
  protected static $slug_field = 'slug';

  public function scopeSlug($q, $slug) {
    $q->where('slug', $slug);
  }
}
