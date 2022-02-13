<?php
/**
 * @var object $Module
 * @var array $records
 */
NFW::i()->registerResource('jquery.activeForm');
NFW::i()->registerResource('jquery.jgrowl');
NFW::i()->registerResource('jquery.ui.interactions');
?>
<script type="text/javascript">
$(document).ready(function(){
	$('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
		var varname = $(e.target).attr('aria-controls');

		if ($('div[role="tabpanel"][id="' + varname + '"]').attr('data-loaded') == '1') return;

		$('div[role="tabpanel"][id="' + varname + '"]').attr('data-loaded', '1');
		$('div[role="tabpanel"][id="' + varname + '"]').load($(e.target).attr('data-source')); 
	});
	$('#settings-tabs a:first').tab('show');
});
</script>

<ul id="settings-tabs" class="nav nav-tabs" role="tablist">
	<?php foreach ($records as $r) { ?>
		<li role="presentation"><a data-source="<?php echo $Module->formatURL('update').'&varname='.$r['varname']?>" href="#<?php echo $r['varname']?>" aria-controls="<?php echo $r['varname']?>" role="tab" data-toggle="tab"><?php echo htmlspecialchars($r['name'])?></a></li>
	<?php } ?>
</ul>

<div class="tab-content">
	<?php foreach ($records as $r) { ?>
	<div role="tabpanel" class="tab-pane" id="<?php echo $r['varname']?>"></div>
	<?php } ?>
</div>