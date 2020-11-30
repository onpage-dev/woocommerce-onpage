<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

use WeDevs\ORM\Eloquent\Model;

class TermTaxonomy extends Model {
    protected $table = PFX.'term_taxonomy';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'term_taxonomy_id';

    public function term() {
      return $this->belongsTo(Term::class, 'term_id', 'term_id');
    }

    function getIdAttribute() {
        return $this->attributes[$this->primaryKey];
    }
}
