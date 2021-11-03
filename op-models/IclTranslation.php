<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

use WeDevs\ORM\Eloquent\Model;

class IclTranslation extends Model {
    protected $table = OP_WP_PREFIX.'icl_translations';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'translation_id';

    function getIdAttribute() {
        return $this->attributes[$this->primaryKey];
    }
}
