<?php
if (!defined('OP_PLUGIN')) die(400);
// error_reporting(E_ALL ^ E_NOTICE);
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (!$item || !$item->resource) return;

if (!$item->resource->is_product) echo '<tr><td colspan="2">';
?>
<div id="onpage_meta" class="panel woocommerce_options_panel">
  <h1 style="margin: 10px 20px 0">OnPage Fields</h1>
  <div>
    ID: <b>#<?= $item->getId() ?></b>
  </div>
  <div>
    Resource: <b><?= op_label($item->resource) ?></b>
  </div>
  <table>
    <tbody>
      <?php foreach (collect($item->resource->fields)->sortBy(function($f) { if ($f->type == 'relation') return 9999+$f->order; else return 1+$f->order; }) as $f): ?>
        <?php $values = $f->is_multiple ? $item->val($f->name) : [$item->val($f->name)]; ?>
        <tr>
           <td>
             <b><?= op_e($f->label) ?></b>
             <br>
             <span style="font-family: monospace; color: #666"><?= op_e($f->name) ?></span>
             <br>
             <span style="font-family: monospace; color: #666"><?= op_e($f->type) ?></span>
             <br>
             <?=$f->is_translatable ? 'Localized' : 'Not localized'?>
             -
             <?=$f->is_multiple ? 'Multivalue' : 'Single'?>
           </td>
           <td>
             <div style="overflow-y: auto; max-height: 220px;">
               <?php if ($f->type == 'file' || $f->type == 'image'): ?>
                 <?php $files = $f->is_multiple ? $item->file($f->name) : array_filter([$item->file($f->name)]); ?>
                 <?php foreach ($files as $file): ?>
                   <a target="_blank" href="<?= $file->link() ?>" class="op-file">
                     <?php if ($f->type == 'image'): ?>
                       <img src="<?= op_e($file->thumb(null, 150)) ?>"
                       style="border: 1px solid #ddd"/>
                       <br>
                     <?php endif ?>
                     <?= op_e($file->name) ?>
                   </a>
                 <?php endforeach ?>
               <?php elseif ($f->type == 'html'): ?>
                 <?php foreach ($values as $v): ?>
                   <?= $v ?>
                 <?php endforeach ?>
               <?php elseif ($f->type == 'relation'): ?>
                 <?= $item->{$f->name}()->count() ?> items
               <?php else: ?>
                 <?php foreach ($values as $v): ?>
                   <?= op_e($v) ?>
                 <?php endforeach ?>
               <?php endif ?>
             </div>
           </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<style media="screen">
#onpage_meta {
  padding: 10px;
}
#onpage_meta table {
  background: #f8f8f8;
  width: 100%;
  padding: 10px;
  margin: 10px 0;
  border: 1px solid #ddd;
}
#onpage_meta table td {
  padding: 10px;
}
#onpage_meta table tr:not(:last-child) td {
  border-bottom: 1px solid #ddd;
}
#onpage_meta table tr:hover td {
  background: #fff;
}
#onpage_meta table td img {
  background: #fff;
  max-width: 200px;
}
#onpage_meta table td input {
  background: #fff;
  color: #000;
}
#onpage_meta .op-file {
  display: inline-flex;
  flex-direction: column;
  margin: 0 0 1rem;
}
</style>
<?php
if (!$item->resource->is_product) echo '</td></tr>';
?>
