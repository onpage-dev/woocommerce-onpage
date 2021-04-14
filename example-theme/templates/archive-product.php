<?php
get_header();
op_debug();
// echo op_category('slug', 'bbb-sottocategoria-21-en')->id;
// echo op_product('post_name', 'bbb-prodotto-11-2-en')->id;

foreach (Op\Categorie::all() as $cat) {
    echo "<div style='margin: 1rem 2rem 5rem; background: #eee; padding: 1rem'>";
    echo "<h2>{$cat->id} - {$cat->getLang()} - {$cat->val('nome')}</h2>";

    foreach ($cat->sottocategorie as $subcat) {
        echo "<div style='margin: 1rem 2rem 2rem; background: #ddd; padding: 1rem'>";
        echo "<h3><a href=\"{$subcat->permalink()}\">{$subcat->val('nome')}</a></h3>";

        foreach ($subcat->prodotti as $prod) {
            echo "<div style='margin: 1rem 2rem 2rem; background: #ccc; padding: 1rem'>";
            echo "<h3><a href=\"{$prod->permalink()}\">{$prod->val('nome')}</a></h3>";

            foreach ($prod->sottocategorie as $prod_subcat) {
                echo "<div style='margin: 1rem 2rem 2rem; background: #ddd; padding: 1rem'>";
                echo "<h3>{$prod_subcat->val('nome')}</h3>";
                echo "</div>";
            }
            echo "</div>";
        }

        echo "</div>";
    }

    echo "</div>";
}
get_footer();
