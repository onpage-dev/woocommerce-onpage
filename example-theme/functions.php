<?php
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('parent', get_template_directory_uri() . '/style.css');
});

// add_action('op_import_relations', function() {
//     return [
//         'prodotti' => 'categorie',
//     ];
// });

add_filter('on_page_product_resources', function () {
    return ['prodotti']; // list of resource names (not labels)
});

add_filter('template_include', function ($template) {
    if (is_woocommerce() && is_archive()) {
        $new_template = get_stylesheet_directory() . '/woocommerce/archive-product.php';
        if (!empty($new_template)) {
            return $new_template;
        }
    }
    return $template;
}, 99);

add_action('op_gen_slug', function ($item) {
    $name = $item->val('nome') ?: $item->val('nome', 'it');
    return "bbb-{$name}-{$item->getLang()}";
});
add_action('op_import_relations', function () {
    return [
        // On Page resource name => // On Page parent relation name
        'sottocategorie' => 'categorie',
    ];
});
