<?=uif::contentHeader($heading.': '.$product->prodname)?>
<?php if (isset($results) AND is_array($results) AND count($results)):?>
<table class="table table-stripped table-hover data-grid"> 
	<thead>
		<tr>
			<th>&nbsp;</th>
			<th><?=uif::lng('attr.date')?></th>
			<th><?=uif::lng('attr.link')?></th>
			<th><?=uif::lng('attr.previous_stock')?></th>
			<th><?=uif::lng('attr.in')?></th>
			<th><?=uif::lng('attr.out')?></th>
			<th><?=uif::lng('attr.document')?></th>
			<?php if($this->session->userdata('admin')):?>
				<th><?=uif::lng('attr.operator')?></i></th>
			<?php endif;?>
		</tr>
	</thead>
	<tbody>
	<?php foreach($results as $row):?>
		<tr>
			<td>
			<?php 
				if(!is_null($row->is_out))
				{
					$icon = 'icon-circle-arrow-up'; $page ='out';
				}
					
				if(is_null($row->is_out) AND is_null($row->is_return))
				{
					$icon = 'icon-circle-arrow-down'; $page ='in';
				}
				elseif(!is_null($row->is_return))
				{
					$icon = 'icon-refresh'; $page ='ret';
				}

				echo uif::staticIcon($icon);			
			?>	
			</td>
			<td><?=uif::date($row->dateoforigin)?></td>
			<td><?=uif::linkIcon("distribution/view/{$page}/{$row->id}",'icon-link')?></td>	
			<td><?=$row->qty_current.' '.$row->uname?></td>	
			<td><?=($row->quantity>0) ? $row->quantity.' '.$row->uname : '-'?></td>
			<td><?=($row->quantity<0) ? $row->quantity.' '.$row->uname : '-'?></td>
			<td><?=uif::isNull($row->ext_doc)?></td>
			<?php if($this->session->userdata('admin')):?>
				<td><?=$row->assignfname. ' ' . $row->assignlname;?></td>
			<?php endif;?>
		</tr>
	<?php endforeach;?>
		</tbody>
</table>
<?php else:?>
	<?=uif::load('_no_records')?>
<?php endif;?>