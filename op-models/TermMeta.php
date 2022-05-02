<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

use WeDevs\ORM\Eloquent\Model;

class TermMeta extends Model {
    protected $table = OP_WP_PREFIX.'termmeta';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'meta_id';
    public static $relation_field = 'term_id';

    public function parent() {
      return $this->belongsTo(Term::class, 'term_id');
    }
}
