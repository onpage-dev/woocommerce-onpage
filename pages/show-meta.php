<?php
if (!defined('OP_PLUGIN')) die(400);

if (!$item->resource->is_product) echo '<tr><td colspan="2">';
?>
<div id="onpage_meta" class="panel woocommerce_options_panel">
  <h1 style="margin: 10px 20px 0">OnPage Fields - <?= op_e($item->resource->label) ?></h1>
  <table>
    <tbody>
      <?php foreach (collect($item->resource->fields)->whereNotIn('type', ['relation']) as $f): ?>
        <tr>
           <td>
             <b><?= op_e($f->label) ?></b>
             <br>
             <span style="font-family: monospace; color: #666"><?= op_e($f->name) ?></span>
           </td>
           <td>
             <?php if ($f->type == 'file'): ?>
               <a target="_blank" href="<?= $item->url($f->name) ?>">
                 <?= $item->filename($f->name) ?>
               </a>
             <?php elseif ($f->type == 'image'): ?>
               <a target="_blank" href="<?= $item->url($f->name) ?>">
                 <img src="<?= $item->thumb($f->name, null, 200) ?>"
                 style="border: 1px solid #ddd"/>
                 <br>
                 <?= $item->filename($f->name) ?>
               </a>
             <?php elseif ($f->type == 'html'): ?>
               <?= $item->val_unsafe($f->name) ?>
             <?php elseif ($f->type == 'relation'): ?>
               --
             <?php else: ?>
               <input type="text" disabled style="width: 100%" value="<?= $item->val($f->name) ?>">
             <?php endif ?>
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
}
#onpage_meta table td input {
  background: #fff;
  color: #000;
}
</style>
<?php
if (!$item->resource->is_product) echo '</td></tr>';
?>
