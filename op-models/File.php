<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

class File {
  public function __construct(object $file) {
    $this->name = $file->name;
    $this->ext = $file->ext;
    $this->token = $file->token;
  }

  /** @return string|null the image url */
  public function link() {
    return op_file_url($this);
  }

  /** @return string|null the image url */
  public function cdn(string $cdn_name = null) {
    if (!isset($this->cdn)) return null;

    // Get first cdn available
    if (!$cdn_name) $cdn_name = array_keys($this->cdn)[0];

    // Verify image is present
    if (!isset($this->cdn[$cdn_name])) return null;

    // Return the cdn url
    return $this->cdn[$cdn_name];
  }
  
  public function thumb($w = null, $h = null, $contain = false) {
    return op_file_url($this, $w, $h, $contain);
  }
}
