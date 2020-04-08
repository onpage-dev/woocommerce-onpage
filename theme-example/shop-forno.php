<?php
defined( 'ABSPATH' ) || exit;
get_header();

$forno = $element;
?>
<main id="site-content" role="main">
  <header class="entry-header has-text-align-center header-footer-group">
    <h1>Shoppo</h1>
  </header>
  <article class="page">

    <div class="post-inner thin ">

  		<div class="entry-content">

        <h2><?= $forno->val('nome') ?></h2>
        <img src="<?=$forno->thumb('immagine', 700, 400)?>"/>
        <?=$forno->file('immagine')?>
        <div class="">
          <h4>Power supply:</h4>
          <ul>
            <?php foreach ($forno->power_supply as $pow): ?>
              <li>
                <b><?= $pow->val('nome') ?></b>
              </li>
            <?php endforeach ?>
          </ul>
        </div>

        <div class="">
          <h4>Connection positions:</h4>
          <ul>
            <?php foreach ($forno->connection_positions as $conn): ?>
              <li>
                <b><?=$conn->val('descr') ?></b>
                <i><?=$conn->val('descr', 'it') ?></i>
              </li>
            <?php endforeach ?>
          </ul>
        </div>


        <h5>
          RAM: <?=number_format(memory_get_usage()/1000/1000, 2)?>MB
        </h5>


  		</div>

  	</div>
  </article>
</main>

<?php get_footer(); ?>
