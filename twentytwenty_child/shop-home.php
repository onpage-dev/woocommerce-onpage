<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="site-content" role="main">
  <header class="entry-header has-text-align-center header-footer-group">
    <h1>Shoppo</h1>
  </header>
  <article class="page">
    <div class="post-inner thin ">
  		<div class="entry-content">

        <h2>Home</h2>
        <ul>
          <?= print_r( Op\Settore::pluckField('nome'), 1) ?>
          <?php foreach (Op\Settore::with(['gamme' => function($q) {
            $q->whereField('nome', 'like', '%top%');
          }])->get() as $settore): ?>
            <li>
              <b><?= $settore->val('nome') ?></b>

              <ul>
                <?php foreach ($settore->gamme as $gamma): ?>
                  <li>
                    <a href="<?= $gamma->link() ?>">
                      <b><?= $gamma->val('nome') ?></b>
                    </a>
                    <br>
                    <code><?= $gamma->link() ?></code>
                  </li>
                <?php endforeach ?>
              </ul>

            </li>
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
