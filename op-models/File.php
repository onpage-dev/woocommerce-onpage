<?php
namespace OpLib;

if (!defined('OP_PLUGIN')) die(400);

class File {
  private $json;
  public $name;
  public $ext;
  public $token;
  /** @var array|null (e.g. [0.5, 0.5]) */
  public $focus;

  public function __construct(object $file) {
    $this->json = $file;
    $this->name = $file->name;
    $this->ext = $file->ext;
    $this->token = $file->token;
    if (isset($file->focus) && is_array($file->focus) && count($file->focus) === 2) {
      $this->focus = $file->focus;
    } else {
      $this->focus = null;
    }
  }

  /** @return string|null the image url */
  public function link(bool $inline = false) {
    return op_file_url($this, null, null, null, $inline);
  }

  public function onPageLink(bool $inline = false) {
    return op_file_url($this, null, null, null, $inline, true);
  }

  /** @return string|null the image url */
  public function cdn(string $cdn_name = null) {
    if (!isset($this->json->cdn)) return null;

    $cdn = (array) $this->json->cdn;

    if (empty($cdn)) return null;

    // Get first cdn available
    if (!$cdn_name) $cdn_name = array_keys($cdn)[0];

    // Verify image is present
    if (!isset($cdn[$cdn_name])) return null;

    // Return the cdn url
    return $cdn[$cdn_name];
  }

  public function thumb($w = null, $h = null, $contain = false) {
    return op_file_url($this, $w, $h, $contain);
  }

  public function onPageThumb($w = null, $h = null, $contain = false) {
    return op_file_url($this, $w, $h, $contain, false, true);
  }

  function getWidth() {
    if (!isset($this->json->width)) return null;
    return $this->json->width;
  }
  function getHeight() {
    if (!isset($this->json->height)) return null;
    return $this->json->height;
  }
  function getAverageColor() {
    if (!isset($this->json->color_r)) return null;
    return "rgb({$this->json->color_r},{$this->json->color_g},{$this->json->color_b})";
  }
}
