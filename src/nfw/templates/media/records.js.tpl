<?php
/**
 * @var array $records 
 */
$lang_media = NFW::i()->getLang('media');
ob_start();
?>
{
<?php if (isset($_GET['ui']) && $_GET['ui'] == 'bootstrap'): ?>
	"sRowTemplate": <?php echo json_encode('<tr><td style="white-space: nowrap;"><a href="%url%" target="_blank" type="%type%"><img src="%icon_medium%" /></a></td><td style="width: 100%;"><input id="%id%" rel="comment" type="text" class="form-control" value="%comment%" /></td><td style="white-space: nowrap;"><a rel="reload-media-file" href="#" id="%id%" class="btn btn-mini btn-warning" title="'.$lang_media['Reload'].'">R</a>&nbsp;<a rel="remove-media-file" href="#" id="%id%" class="btn btn-xs btn-danger" title="'.$lang_media['Remove'].'"><span class="glyphicon glyphicon-remove"></span></a></td>')?>,
<?php endif; ?>
	"iTotalRecords":<?php echo count($records)?>,
	"aaData": [
	<?php $records_counter = 0; $session_size = 0; 
		foreach ($records as $a) { $session_size += $a['filesize'];?>{
		"id": <?php echo $a['id']?>,
		"type": "<?php echo $a['type']?>",
		"posted": <?php echo $a['posted']?>,
		"posted_str": "<?php echo date('d.m.Y', $a['posted'])?>",
		"filesize_str": "<?php echo $a['filesize_str']?>",
		"icon": "<?php echo $a['icons']['16x16']?>",
		"icon_medium": "<?php echo $a['icons']['32x32']?>",
		"url": "<?php echo $a['url']?>",
		"filename": <?php echo json_encode($a['filename'])?>,
		"basename": <?php echo json_encode($a['basename'])?>,
		"extension": <?php echo json_encode($a['extension'])?>,
		"tmb_prefix": <?php echo isset($a['tmb_prefix']) ? json_encode($a['tmb_prefix']) : 'null'?>,
		"comment": <?php echo $a['comment'] ? json_encode($a['comment']) : '""'?>
	}<?php if ($records_counter++ < count($records) - 1) echo ','; }?>
	],
	"iSessionSize": <?php echo $session_size?>,
	"iSessionSize_str": "<?php echo number_format($session_size/1048576, 2, '.', ' ')?>"
}
<?php
	echo preg_replace('!\s+!u', ' ', ob_get_clean()); 