<?php
if (!defined('OP_PLUGIN')) die(400);
$prod = OpLib\Post::find($post->ID);
?>
<div id="onpage_meta" class="panel woocommerce_options_panel">
  <h1 style="margin: 10px 20px 0">OnPage Fields - <?= op_e($prod->resource->label) ?></h1>
  <table>
    <tbody>
      <?php foreach (collect($prod->resource->fields)->whereNotIn('type', ['relation']) as $f): ?>
        <tr>
           <td>
             <b><?= op_e($f->label) ?></b>
             <br>
             <span style="font-family: monospace; color: #666"><?= op_e($f->name) ?></span>
           </td>
           <td>
             <?php if ($f->type == 'file'): ?>
               <a target="_blank" href="<?= $prod->url($f->name) ?>">
                 <?= $prod->filename($f->name) ?>
               </a>
             <?php elseif ($f->type == 'image'): ?>
               <a target="_blank" href="<?= $prod->url($f->name) ?>">
                 <img src="<?= $prod->thumb($f->name, 200, 150) ?>"
                 style="border: 1px solid #ddd"/>
                 <br>
                 <?= $prod->filename($f->name) ?>
               </a>
             <?php elseif ($f->type == 'html'): ?>
               <?= $prod->val_unsafe($f->name) ?>
             <?php elseif ($f->type == 'relation'): ?>
               --
             <?php else: ?>
               <input type="text" disabled style="width: 100%" value="<?= $prod->val($f->name) ?>">
             <?php endif ?>
           </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<style media="screen">
#onpage_meta table td {
  padding: 5px 10px;
}
</style>
