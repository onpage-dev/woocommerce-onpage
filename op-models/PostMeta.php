<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(420);

use WeDevs\ORM\Eloquent\Model;

class PostMeta extends Model {
    protected $table = PFX.'postmeta';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'post_id';

    public function meta() {
      return $this->belongsTo(Post::class);
    }
}
