<?php
/**
 * @var array $records
 */
NFW::i()->registerResource('bootstrap');
NFW::i()->registerResource('font-awesome');
NFW::i()->registerFunction('tmb');
$lang_media = NFW::i()->getLang('media');

$tmb_width = 160;
$tmb_height = 160;

// Generate icon background
$image = imagecreatetruecolor($tmb_width, $tmb_height);
imagesavealpha($image, true);
$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
imagefill($image, 0, 0, $transparent);
ob_start();
imagepng($image);
$icon_container_img = 'data:image/png;base64,'.base64_encode(ob_get_clean());
imagedestroy($image);
?>
<html><head><title><?php echo $lang_media['CKE Choose File']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript">
$(document).ready(function(){
	$('a[role="cke-insert"]').click(function(){
		var url = $(this).attr('href');

		window.opener.CKEDITOR.tools.callFunction(<?php echo $_GET['CKEditorFuncNum']?>, url, '');
		window.close();
		return false;
	});

	$('[role="cke-delete"]').click(function(){
		if (!confirm('<?php echo $lang_media['Remove confirm']?>')) return false;

		
		var item = $(this).closest('[role="record"]');
		var file_id = item.data('id');
		
		$.post('<?php echo NFW::i()->base_path?>media.php?action=remove', { 'file_id': file_id }, function(response){
			if (response == 'success') {
				item.remove();
			}
			else {
				alert(response);
			}
		});
		
		return false;
	});
	
});
</script>
<style>
.thumbnail .overlay { position: absolute; top: 0; left: 0; width: 100%; text-align: center; padding: 20px 20px 0 20px; }
.thumbnail .basename { display: block; white-space: nowrap; overflow: hidden; font-size: 13px; }
.thumbnail .basename a:hover, .thumbnail .basename a:focus { text-decoration: none; }
.thumbnail .actions { text-align: center; }
</style>
</head>
<body>
<div class="container-fluid" style="padding-top: 20px;">
	<?php if (empty($records)):?>
		<div class="alert alert-warning"><?php echo $lang_media['CKE No Files']?></div>
	<?php else:?>
	<h2><?php echo $lang_media['CKE Choose File Header']?></h2>
	<?php foreach ($records as $record) { ?>
		<div data-id="<?php echo $record['id']?>" role="record" class="col-xs-6 col-sm-4 col-md-3 col-lg-3">
			<div class="thumbnail">
				<a href="<?php echo $record['url']?>" role="cke-insert" title="<?php echo htmlspecialchars($record['basename'])?>">
					<?php if ($record['type'] == 'image'):?>
					<img src="<?php echo tmb($record, $tmb_width, $tmb_height, array('complementary' => true))?>" alt="" />
					<?php else: ?>
					<span class="overlay">				
						<img  class="icon" src="<?php echo $record['icons']['64x64']?>"alt="" />
						<span class="basename"><?php echo htmlspecialchars($record['basename'])?></span>
					</span>
					<img src="<?php echo $icon_container_img?>" alt="" />
					<?php endif;?>
				</a>
				<div class="actions">
					<button role="cke-delete" class="btn btn-xs btn-danger"><span class="fa fa-times"></span> <?php echo $lang_media['Remove']?></button>
				</div>
			</div>
		</div>
	<?php } ?>	
	<?php endif; ?>
</div>
</body></html>