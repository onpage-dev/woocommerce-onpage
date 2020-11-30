<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

use WeDevs\ORM\Eloquent\Model;

class PostMeta extends Model {
    protected $table = PFX.'postmeta';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'meta_id';

    public function parent() {
      return $this->belongsTo(Post::class, 'post_id');
    }
}
