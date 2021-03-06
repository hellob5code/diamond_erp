<?=uif::contentHeader($heading)?>
<div class="row-fluid">
	<div class="span12" id="content-main-buttons">
		<?=uif::linkButton('inventory/insert_gr','icon-download-alt')?>
	</div>
</div>
<hr>
<?php if (isset($results) AND is_array($results) AND count($results)):?>
<table class="table table-stripped table-hover data-grid"> 
	<thead>
		<tr>
			<th>&nbsp;</th>
			<th><?=uif::lng('attr.item')?></th>
			<th><?=uif::lng('attr.category')?></th>
			<th><?=uif::lng('attr.stock')?></th>
			<th><?=uif::lng('attr.price_min')?></th>
			<th><?=uif::lng('attr.price_max')?></th>
			<th><?=uif::lng('attr.last_change')?></th>
		</tr>
	</thead> 
	<tbody>
		<?php foreach($results as $row):?>
		<tr <?=($row->alert_quantity >= $row->quantity) ? ' class="warning" '  : ''?>>
			<td><?=uif::linkIcon("inventory/digg/{$row->pid}",'icon-folder-open')?></td>
			<td><?=$row->prodname?></td>
			<td><?=$row->pcname?></td>
			<td><?=$row->quantity.' '.$row->uname?></td>
			<td><?=round($row->price,3).$glCurrSh?></td>
			<td><?=round($row->maxprice,3).$glCurrSh?></td>
			<td><?=uif::date($row->dateofentry)?></td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<?php else:?>
	<?=uif::load('_no_records')?>
<?php endif;?>