<?php
defined( 'ABSPATH' ) || exit;
get_header();

$gamma = $element;
?>
<main id="site-content" role="main">
  <header class="entry-header has-text-align-center header-footer-group">
    <h1>Shoppo</h1>
  </header>
  <article class="page">

    <div class="post-inner thin ">

  		<div class="entry-content">

        <h2><?= $gamma->val('nome') ?></h2>
        <h4>Forni disponibili:</h4>

        <ul>
          <?php foreach ($gamma->versione()->with('alimentazione.modello.versione_teglie.forni')->get() as $versione): ?>
            <?php foreach ($versione->alimentazione as $alimentazione): ?>
              <?php foreach ($alimentazione->modello as $modello): ?>
                <?php foreach ($modello->versione_teglie as $versione_teglie): ?>
                  <?php foreach ($versione_teglie->forni as $forno): ?>
                    <li>
                      <img src="<?=$forno->thumb('immagine', 100, 100)?>"/>
                      <a href="./<?=$forno->post_name?>">
                        <b><?= $forno->val('nome') ?></b>
                      </a>
                    </li>
                  <?php endforeach ?>
                <?php endforeach ?>
              <?php endforeach ?>
            <?php endforeach ?>
          <?php endforeach ?>
        </ul>

        <h5>
          RAM: <?=number_format(memory_get_usage()/1000/1000, 2)?>MB
        </h5>


  		</div>

  	</div>
  </article>
</main>

<?php get_footer(); ?>
