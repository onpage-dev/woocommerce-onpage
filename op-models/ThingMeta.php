<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

use WeDevs\ORM\Eloquent\Model;

class ThingMeta extends Model {
    protected $table = OP_WP_PREFIX.'op_thingmeta';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'meta_id';
    public static $relation_field = 'thing_id';

    public function parent() {
      return $this->belongsTo(Thing::class, 'thing_id');
    }
}
