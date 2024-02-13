<?php

namespace WpEloquent\WP;


use WpEloquent\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = 'ID';
    protected $timestamp = false;

    public function meta()
    {
        return $this->hasMany('WpEloquent\WP\UserMeta', 'user_id');
    }
}