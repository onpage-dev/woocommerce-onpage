<?php

add_action('wp_enqueue_scripts', function() {
  wp_enqueue_style( 'parent', get_template_directory_uri() . '/style.css' );
});



op_page('/', function($el) {
  include __DIR__.'/shop-home.php';
});
op_page('/settore.gamme', function($gamma) {
  include __DIR__.'/shop-gamma.php';
});
op_page('/settore.gamme/versione.alimentazione.modello.versione_teglie.forni', function($forno) {
  include __DIR__.'/shop-forno.php';
});
