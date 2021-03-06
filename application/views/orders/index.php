<?=uif::contentHeader($heading)?>
<div class="row-fluid">
	<div class="span12 text-right" id="content-main-filters">
		<?=form_open('orders/search','class="form-inline"')?>
			<?=uif::formElement('dropdown','','partner_fk',[$customers])?>
			<?=uif::formElement('dropdown','','distributor_fk',[$distributors])?>
			<?=uif::formElement('dropdown','','payment_mode_fk',[$modes_payment])?>
			<?=uif::formElement('dropdown','','postalcode_fk',[$postalcodes])?>
			<?=uif::filterButton()?>
    	<?=form_close()?>
	</div>
</div>
<hr>
<?php if (isset($results) AND is_array($results) AND count($results)):?>
<table class="table table-stripped table-hover data-grid">
	<thead>
		<tr>
	    	<th colspan="2">&nbsp;</th>
	    	<?php foreach ($columns as $col_name => $col_display):?>
	    		<th <?=($sort_by==$col_name) ? "class={$sort_order}" : "";?>>
	    			<?php echo anchor("orders/index/{$query_id}/{$col_name}/".
	    			(($sort_order=='desc' AND $sort_by==$col_name)?'asc':'desc'),$col_display);?>
	    		</th>
		    <?php endforeach;?>
	    	<th>&nbsp;</th>
	    </tr>
    </thead>
    <tbody>
		<?php foreach($results as $row):?>
		<tr data-id=<?=$row->id?>>
			<td><?=uif::viewIcon('orders',$row->id)?></td>
			<td><?=($row->locked) ? uif::staticIcon('icon-lock') : ''?></td>
			<td><?=uif::date($row->dateshipped)?></td>
			<td><?=$row->company;?></td>
			<td><?=$row->fname . ' ' . $row->lname; ?></td>
			<td><?=uif::isNull($row->name)?></td>
			<td><?=uif::date($row->dateofentry)?></td>
			<td><?=($row->order_list_id) ? 
				uif::linkIcon("orders_list/view/{$row->order_list_id}",'icon-link'):'-';?></td>	
			<td><?=(!$row->locked) ? uif::actionGroup('orders',$row->id) : ''?></td>
		</tr>
		<?php endforeach;?>
	</tbody> 
</table>
<?php else:?>
	<?=uif::load('_no_records')?>
<?php endif;?>
<script>
	$(function(){
		cd.dd("select[name=distributor_fk]","<?=uif::lng('attr.distributor')?>");
		cd.dd("select[name=partner_fk]","<?=uif::lng('attr.partner')?>");
		cd.dd("select[name=payment_mode_fk]","<?=uif::lng('attr.payment_method')?>");
		cd.dd("select[name=postalcode_fk]","<?=uif::lng('attr.city')?>");
	});	
</script>