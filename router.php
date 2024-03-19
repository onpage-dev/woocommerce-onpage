<?php

if (!defined('OP_PLUGIN')) {
    die(400);
}

add_filter('init', function () {
    $uri = explode('?', @$_SERVER['REQUEST_URI'])[0];
    $shop_url = @op_settings()->shop_url;
    if (empty(op_page())) {
        return;
    }

    add_filter('redirect_canonical', function ($redirect_url) {
        if (is_404()) {
            return false;
        }
        return $redirect_url;
    });

    $shop_url = trim($shop_url, '/');
    $slash = '\/';
    $pieces = explode('/', $uri);
    $pieces = array_filter($pieces);
    $pieces = array_values($pieces);
    if (@$pieces[0] !== $shop_url) {
        return;
    }
    array_shift($pieces);

    $pages = op_page();
    foreach ($pages as $page => $action) {
        $page = explode('/', $page);
        $page = array_filter($page);
        $page = array_values($page);
        if (count($page) === count($pieces)) {
            op_use_page($action, $page, $pieces);
        }
    }
}, 9999999);

function op_page_query($path, $q = null, $with = false, $i = null) {
    if (is_null($i)) {
        $i = count($path) - 1;
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
            $q->with([$p['rel']->name => function ($q) use ($path, $i) {
                op_page_query($path, $q, true, $i - 1);
            }]);
        } else {
            $q->whereHas($p['rel']->name, function ($q) use ($path, $i) {
                op_page_query($path, $q, false, $i - 1);
            });
        }
    }

    return $q;
}

function op_build_page_path(array $page, array $pieces = []) {
    $path = [];
    foreach ($page as $i => $step) {
        $rels = explode('.', $step);
        if ($i === 0) {
            $class_name = array_shift($rels);
            $class_name = "\\Op\\$class_name";
            $res = @$class_name::getResource();
            if (!$res) {
                throw new Exception("Resource not found: $class_name");
            }
            $path = [
                [
                    'res' => $res,
                ],
            ];
        }
        foreach ($rels as $rel_name) {
            $prev_res = $path[count($path) - 1]['res'];
            $rel_field = @$prev_res->name_to_field[$rel_name];
            if (!$rel_field) {
                throw new Exception("Relation not found: $rel_name");
            }
            $path[] = [
                'rel' => $rel_field->rel_field,
                'res' => $rel_field->rel_res,
            ];
        }
        $path[count($path) - 1]['slug'] = @$pieces[$i];
    }
    return $path;
}

function op_use_page($action, $page, $pieces) {
    if (count($pieces)) {
        $schema = op_schema();
        $path = op_build_page_path($page, $pieces);

        $query = op_page_query($path);
        $query = op_page_query($path, $query, true);

        $element = $query->first();

        if (!$element) {
            header('Location: /404');
            exit;
            // throw new Exception('Cannot find model 404');
        }
    } else {
        $element = null;
    }

    add_filter('template_include', function ($template) use ($action, $element) {
        op_page_title('On Page Route', 9998);
        $action($element);
        exit;
    }, 9999999);
}

function op_page_title($title, $priority = 9999) {
    add_filter('pre_get_document_title', function () use ($title) {
        return $title;
    }, $priority);
}

function op_link_to($item) {
    $pages = op_page();
    foreach ($pages as $page => $action) {
        $page = explode('/', $page);
        $page = array_filter($page);
        $page = array_values($page);
        $path = op_build_page_path($page);
        $path = array_reverse($path);
        if (@$path[0]['res']->id === $item->resource->id) {
            $link = [];
            foreach ($path as $i => $block) {
                if (array_key_exists('slug', $block)) {
                    $link[] = $item->getSlug();
                    if (count($page) === count($link)) {
                        $link[] = trim(op_settings()->shop_url, '/');
                        $link[] = trim(get_site_url(), '/');
                        $link = array_reverse($link);
                        return implode('/', $link);
                    };
                }
                // var_dump($block['rel']->name);
                $item = $item->{$block['rel']->name}()->first();
                if (!$item) {
                    break;
                }
            }
        }
    }
}
