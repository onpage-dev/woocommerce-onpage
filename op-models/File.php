<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

class File {
  private $json;
  public function __construct(object $file) {
    $this->
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
    if (!isset($this->json->cdn)) return null;

    // Get first cdn available
    if (!$cdn_name) $cdn_name = array_keys($this->json->cdn)[0];

    // Verify image is present
    if (!isset($this->json->cdn[$cdn_name])) return null;

    // Return the cdn url
    return $this->json->cdn[$cdn_name];
  }
  
  public function thumb($w = null, $h = null, $contain = false) {
    return op_file_url($this, $w, $h, $contain);
  }

  function getWidth() {
    if (!isset($this->json->width)) return null;
    return $this->json->width;
  }
  function getHeight() {
    if (!isset($this->json->height)) return null;
    return $this->json->height;
  }
  function getColor() {
    if (!isset($this->json->color_r)) return null;
    return "rgba({$this->json->color_r},{$this->json->color_g},{$this->json->color_b})";
  }
}
