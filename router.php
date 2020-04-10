<?php

if (!defined('OP_PLUGIN')) die(400);


add_filter('init', function() {
  $uri = explode('?', $_SERVER['REQUEST_URI'])[0];
  $shop_url = @op_settings()->shop_url;
  if (!$shop_url) return;
  $shop_url = trim($shop_url, '/');
  $slash = '\/';
  $pieces = explode('/', $uri);
  $pieces = array_filter($pieces);
  $pieces = array_values($pieces);
  if (@$pieces[0] != $shop_url) return;
  array_shift($pieces);

  $pages = op_page();
  foreach ($pages as $page => $file) {
    $page = explode('/', $page);
    $page = array_filter($page);
    $page = array_values($page);
    if (count($page) == count($pieces)) {
      op_use_page($file, $page, $pieces);
    }
  }

}, 9999999);


function op_page_query($path, $q = null, $with = false, $i = null) {
  if (is_null($i)) {
    $i = count($path)-1;
  }

  $p = $path[$i];
  // if (!$with) echo "cerco in {$p['res']->name}<br>\n";

  if (!$q) {
    $class = "Op\\".op_snake_to_camel($p['res']->name);
    $q = $class::slug($p['slug']);
  } elseif (@$p['slug']) {
    $q->slug($p['slug']);
  }

  if (@$p['rel']) {
    // if (!$with) echo "entro in {$p['rel']->name}<br>\n";

    if ($with) {
      $q->with([$p['rel']->name => function($q) use($path, $i) {
        op_page_query($path, $q, true, $i-1);
      }]);
    } else {
      $q->whereHas($p['rel']->name, function($q) use($path, $i) {
        op_page_query($path, $q, false, $i-1);
      });
    }
  }

  return $q;
}

function op_use_page($file, $page, $pieces) {
  if (count($pieces)) {
    $schema = op_schema();
    $path = [];
    foreach ($page as $i => $step) {
      $rels = explode('.', $step);
      if ($i == 0) {
        $res_name = array_shift($rels);
        $res = @$schema->resources->$res_name;
        if (!$res) die("Resource not found: $res_name");
        $path = [
          [
            'res' => $res,
          ],
        ];
      }
      foreach ($rels as $rel_name) {
        $prev_res = $path[count($path)-1]['res'];
        $rel_field = $prev_res->fields->$rel_name;
        if (!$rel_field) die("Relation not found: $rel_name");
        $path[] = [
          'rel' => $rel_field->rel_field,
          'res' => $rel_field->rel_res,
        ];
      }
      $path[count($path)-1]['slug'] = $pieces[$i];
    }

    $query = op_page_query($path);
    $query = op_page_query($path, $query, true);

    $element = $query->first();

    if (!$element) {
      die('Cannot find model 404');
    }
  } else {
    $element = null;
  }


  if (!$file) return die('file not specified for '.count($pieces).' parameters');
  if (!is_file($file)) return die("File not found: $file");

  add_filter('template_include', function($template) use ($file, $element) {
    include($file);
    exit;
  }, 9999999);
}
