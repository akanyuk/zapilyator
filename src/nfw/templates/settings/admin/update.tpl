<?php
/**
 * @var object $Module
 */
foreach ($Module->record['attributes'] as $key=>$a) { 
    $Module->record['attributes'][$key]['style'] = isset($a['style']) ? $a['style'] : 'width: '.(isset($a['width']) ? $a['width'] : '300px;');
} 
?>
<script type="text/javascript">
$(document).ready(function(){
 	// Action 'update'
 	var f = $('form[id="settings-update-<?php echo $Module->record['varname']?>"]')
 	f.activeForm({
		success: function(response) {
			if (response.is_updated) {
				$.jGrowl('<?php echo $Module->lang['Settings saved']?>');
			}
		}
	});

 	// Sortable `values`
 	f.find('div[id="values-area"]').sortable({
		items: 'div[id="record"]',
 		axis: 'y', 
 	 	handle: '.icon'
	});

 	$(document).off('click', '*[data-action="remove-values-record"]').on('click', '*[data-action="remove-values-record"]', function(event){
 	 	if ($(this).closest('div[id="record"]').attr('data-rel') == 'update') {
 	 		if (!confirm('<?php echo $Module->lang['Remove value']?>')) {
 	 			event.preventDefault();
 	 	 		return false;
 	 		}
 	 	}

 	 	$(this).closest('div[id="record"]').remove();
	});

 	f.find('button[id="add-values-record"]').click(function(){
 	 	var tpl = $('div[id="values-record-template-<?php echo $Module->record['varname']?>"]').html();
 	 	f.find('div[id="values-area"]').append(tpl);
 	 	return false;
	});
});
</script>

<div id="values-record-template-<?php echo $Module->record['varname']?>" style="display: none;">
	<div id="record" class="record" data-rel="insert">
		<div class="cell"><span class="icon glyphicon glyphicon-sort" title="<?php echo $Module->lang['Sort']?>"></span></div>
		<?php foreach ($Module->record['attributes'] as $key=>$a) { ?>
			<div class="cell"><input type="text" class="form-control" name="values[<?php echo $key?>][]" style="<?php echo $a['style']?>" placeholder="<?php echo $a['desc']?>" <?php echo isset($a['required']) && $a['required'] ? 'required' : ''?> /></div>
		<?php } ?>
		<div class="cell"><button data-action="remove-values-record" class="btn btn-danger btn-xs" title="<?php echo NFW::i()->lang['Remove']?>"><span class="glyphicon glyphicon-remove"></span></button></div>
	</div>
</div>

<br />
<form id="settings-update-<?php echo $Module->record['varname']?>" action="<?php echo $Module->formatURL('update').'&varname='.$Module->record['varname']?>">
	<div id="values-area" class="settings">
		<div class="header">
			<div class="cell"></div>
			<?php foreach ($Module->record['attributes'] as $key=>$a) { ?>
				<div class="cell"><?php echo $a['desc']?></div>
			<?php } ?>
		</div>
		<?php foreach ($Module->record['values'] as $v) { ?>
			<div id="record" class="record" data-rel="update">
				<div class="cell"><span class="icon glyphicon glyphicon-sort" title="<?php echo $Module->lang['Sort']?>"></span></div>
				<?php foreach ($Module->record['attributes'] as $key=>$a) { ?>
					<div class="cell"><input type="text" class="form-control" name="values[<?php echo $key?>][]" value="<?php echo $v[$key]?>" style="<?php echo $a['style']?>" placeholder="<?php echo $a['desc']?>" <?php echo isset($a['required']) && $a['required'] ? 'required' : ''?> /></div>
				<?php } ?>
				<div class="cell"><button data-action="remove-values-record" class="btn btn-danger btn-xs" title="<?php echo NFW::i()->lang['Remove']?>"><span class="glyphicon glyphicon-remove"></span></button></div>
			</div>
		<?php } ?>
	</div>
	
	<div style="padding-top: 20px;">
		<button id="add-values-record" class="btn btn-default"><?php echo $Module->lang['Add value']?></button>
		<button type="submit" name="form-send" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> <?php echo NFW::i()->lang['Save changes']?></button>
	</div>
</form>