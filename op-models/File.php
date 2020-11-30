<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

class File {
  public function __construct(object $file) {
    $this->name = $file->name;
    $this->ext = $file->ext;
    $this->token = $file->token;
  }

  public function link() {
    return op_file_url($this);
  }
  
  public function thumb($w = null, $h = null, $contain = false) {
    return op_file_url($this, $w, $h, $contain);
  }
}
