<?php
/**
 * @desc Media Form with multiple uploads and sorting
 * @desc Records always preloaded by form template. Newer reloading (no 'load' event)
 * @version 2018.12.05
 * @var object $Module
 * @var string $session_id 
 * @var int $owner_id
 * @var string $owner_class
 * @var int $MAX_FILE_SIZE
 * @var int $MAX_SESSION_SIZE
 * @var int $image_max_x
 * @var int $image_max_y
 * @var int $tmb_width
 * @var int $tmb_height
 */
NFW::i()->registerResource('jquery.file-upload');
NFW::i()->registerResource('jquery.activeForm');
NFW::i()->registerFunction('limit_text');

$lang_media = NFW::i()->getLang('media');

// Prefetch records and calculate session size
$records = array();
$session_size = 0;
foreach ($owner_id ? $Module->getFiles($owner_class, $owner_id, array('order_by' => 'position')) : array() as $record) {
	$session_size += $record['filesize'];
	$records[] = $record;
}

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
<script>
$(function () {
	var form = $('form[id="<?php echo $session_id?>"]');
	var mediaContainer = form.find('#media-list');
	var propertiesDialog = $('div[id="<?php echo $session_id?>-properties-dialog"]');

	form.trigger('reset');
	
	form.find('input[type="file"]').fileupload({
        dataType: 'json',
        dropZone: form.find('#dropzone'),
		add: function (e, data) {
			$.each(data.files, function (index, file) {
				form.find('#uploading-status').show();	// show log
				form.find('#uploading-status > p').slice(0, -5).remove();	// reduce log
				data.context = $('<p/>').html('<div class="status"><span class="fa fa-spinner"></span></div><div class="log"><?php echo $lang_media['Uploading']?>: ' + file.name + '</div>').appendTo(form.find('#uploading-status'));
			});
			
			data.submit();
		},    
        done: function (e, data) {
            var response = data.result;

            data.context.find('.status').remove();	// remove spinner
            
            if (response.result == 'error') {
            	data.context.append('<div class="text-danger error">' + response.last_message + '</div>');
                return;
            }

            data.context.prepend('<div class="text-success status"><span class="fa fa-check"></span></div>');

            if (response.type == 'image') {
				var tpl = form.find('#record-image-template').html();
            }
            else {
            	var tpl = form.find('#record-file-template').html();
            }
            
			tpl = tpl.replace(/%id%/g, response.id);
			tpl = tpl.replace(/%basename%/g, response.basename);
			tpl = tpl.replace('%type%', response.type); 
			tpl = tpl.replace('%url%', response.url);
			tpl = tpl.replace('%tmbsrc%', 'src="' + response.tmb_prefix + '<?php echo $tmb_width?>x<?php echo $tmb_height?>-cmp.' + response.extension + '"');
			tpl = tpl.replace('%iconsrc%', 'src="' + response.icons['64x64'] + '"');
			tpl = tpl.replace('%comment%', response.comment);
			tpl = tpl.replace('%filesize%', response.filesize_str);
			tpl = tpl.replace('%posted%', response.posted);
			mediaContainer.append(tpl);
			
            form.find('*[id="session-size"]').text(number_format(response.iSessionSize/1048576, 2, '.', ' '));
        }
    });

	mediaContainer.sortable({
		update: function(event, ui) {
 	 		var aPositions = [];
 	 		var iCurPos = 1;
 	 		mediaContainer.find('[role="record"]').each(function(){
 				aPositions.push({ 'id': $(this).attr('id'), 'position': iCurPos++ });
 			});

			$.post('<?php echo NFW::i()->base_path.'media.php?action=sort&session_id='.$session_id?>', { 'positions': aPositions }, function(response){
				if (response == 'success') {
					$.jGrowl('<?php echo $lang_media['Messages']['Changes Saved']?>');
				}
				else {
					alert(response);
				}

				return false;
 			});
		}
	});
		
	propertiesDialog.modal({ 'show': false });

	$(document).on('click', '[role="<?php echo $session_id?>-file-properties"]', function(){
		if ($(this).data('type') == 'image') { 
			propertiesDialog.find('#type-image').show();
			propertiesDialog.find('#type-file').hide();
		}
		else {
			propertiesDialog.find('#type-image').hide();
			propertiesDialog.find('#type-file').show();
		}

		propertiesDialog.find('[id="preview"]').attr('src', $(this).attr('href'));
		propertiesDialog.find('[id="url"]').html('<a href="' + $(this).attr('href') + '" target="_blank">' + $(this).data('basename') + '</a>');
		propertiesDialog.find('[id="filesize"]').text($(this).data('filesize'));
		propertiesDialog.find('[id="posted"]').text(formatDateTime($(this).data('posted'), true, true));

		propertiesDialog.find('[name="record_id"]').val($(this).closest('[role="record"]').attr('id'));
		propertiesDialog.find('[name="basename"]').val($(this).data('basename'));
		propertiesDialog.find('[name="comment"]').val( $(this).closest('[role="record"]').find('#comment').text());		

		propertiesDialog.modal('show');
		return false;
	});

	propertiesDialog.find('form[role="update"]').each(function(){
 		$(this).activeForm({
 	 		action: '<?php echo NFW::i()->base_path?>media.php?action=update&record_id=' + propertiesDialog.data('record-id'),
 			success: function(response) {
 				var oRow = mediaContainer.find('[role="record"][id="' + propertiesDialog.find('[name="record_id"]').val() + '"]');
 				oRow.find('#comment').text(response.comment);
 				oRow.find('[id="basename"]').text(response.basename);
 				oRow.find('a[role="<?php echo $session_id?>-file-properties"]').attr('href', response.url);
 				oRow.find('a[role="<?php echo $session_id?>-file-properties"]').data('basename', response.basename); 

 				propertiesDialog.modal('hide');	 			
 			}
 		});
	});


	propertiesDialog.find('[role="save"]').click(function(){
		propertiesDialog.find('form[role="update"]:visible').submit();
		return false;
	});

	propertiesDialog.find('[role="delete"]').click(function(){
		var recordID = propertiesDialog.find('[name="record_id"]').val();
		if (!recordID) return;
		
		if (!confirm('<?php echo $lang_media['Remove confirm']?>')) return false;

		$.post('<?php echo NFW::i()->base_path?>media.php?action=remove', { 'file_id': recordID }, function(response){
			if (response != 'success') {
				alert(response);
				return;
			}
			
			mediaContainer.find('[role="record"][id="' + recordID + '"]').remove();
			propertiesDialog.modal('hide');
		});
		
		return false;
	});


	// Dropzones for multiple forms
	
	$(document).bind('dragover', function (e) {
		var dropZones = $('.dropzone');
		var timeout = window.dropZoneTimeout;
		
		if (timeout) {
			clearTimeout(timeout);
		} else {
			dropZones.addClass('in');
		}
		
		var hoveredDropZone = $(e.target).closest(dropZones);

		dropZones.not(hoveredDropZone).removeClass('hover');

		hoveredDropZone.addClass('hover');
		
		window.dropZoneTimeout = setTimeout(function () {
			window.dropZoneTimeout = null;
			dropZones.removeClass('in hover');
		}, 100);
	});	
});
</script>
<style>
	FORM#<?php echo $session_id?> .dropzone { display: none; background-color: #b6efb6; border-color: #769e84; padding-top: 50px; padding-bottom: 50px; text-align: center; font-weight: bold; }
	FORM#<?php echo $session_id?> .dropzone.in { display: block; }
	FORM#<?php echo $session_id?> .dropzone.hover { display: block; background: #46af46; }
	FORM#<?php echo $session_id?> .dropzone.fade { -webkit-transition: all 0.3s ease-out; -moz-transition: all 0.3s ease-out; -ms-transition: all 0.3s ease-out; -o-transition: all 0.3s ease-out; transition: all 0.3s ease-out; opacity: 1;	}

	FORM#<?php echo $session_id?> .uploading-status { margin-top: 20px; background-color: #f4f4f4; border: 1px solid #cacaca; border-radius: 4px; padding: 10px; }
	FORM#<?php echo $session_id?> .uploading-status .log { white-space: nowrap; overflow: hidden; margin-right: 20px; font-size: 14px; }
	FORM#<?php echo $session_id?> .uploading-status .status { float: right; position: absolute; right: 24px; }
	FORM#<?php echo $session_id?> .uploading-status .error { overflow: auto; white-space: normal; font-size: 90%; }

	FORM#<?php echo $session_id?> .thumbnail .overlay { position: absolute; top: 0; left: 0; width: 100%; text-align: center; padding: 20px 20px 0 20px; }
	FORM#<?php echo $session_id?> .thumbnail .icon { }
	FORM#<?php echo $session_id?> .thumbnail .basename { display: block; white-space: nowrap; overflow: hidden; font-size: 13px; }
	FORM#<?php echo $session_id?> .thumbnail .basename a:hover, FORM#<?php echo $session_id?> .thumbnail .basename a:focus { text-decoration: none; }
	
	DIV[id="<?php echo $session_id?>-properties-dialog"] .preview-container { text-align: center; }
	DIV[id="<?php echo $session_id?>-properties-dialog"] #preview { max-height: 600px; max-width: 600px; }
	DIV[id="<?php echo $session_id?>-properties-dialog"] FORM[role="update"] LABEL { padding-top: 0; margin-bottom: 0; }
</style>

<div id="<?php echo $session_id?>-properties-dialog" class="modal fade">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><?php echo $lang_media['File properties']?></h4>
			</div>
			
			<div class="modal-body" id="type-image">
				<div class="row">
					<div class="col-md-9 preview-container"><img id="preview" alt="" /></div>
					<div class="col-md-3">
						<form role="update" class="active-form">
							<input type="hidden" name="record_id" />
							
							<div class="form-group">
								<label><strong><?php echo $lang_media['File']?></strong></label>
								<div id="url"></div>	
							</div>

							<div class="form-group">
								<label><strong><?php echo $lang_media['Filesize']?></strong></label>
								<div id="filesize"></div>	
							</div>

							<div class="form-group">
								<label><strong><?php echo $lang_media['Uploaded']?></strong></label>
								<div id="posted"></div>	
							</div>
							<?php echo active_field(array('name' => 'basename', 'attributes'=>$Module->attributes['basename'], 'desc' => $lang_media['Filename'], 'vertical' => true))?>
							<?php echo active_field(array('name' => 'comment', 'attributes'=>$Module->attributes['comment'], 'desc' => $lang_media['Comment'], 'vertical' => true))?>
						</form>
					</div>
				</div>
			</div>
			
			<div class="modal-body" id="type-file">
				<form role="update" class="form-horizontal active-form">
					<input type="hidden" name="record_id" />
				
					<div class="form-group">
						<label for="title" class="col-md-3 control-label"><strong><?php echo $lang_media['File']?></strong></label>
						<div class="col-md-9">
							<div id="url"></div>
						</div>			
					</div>

					<div class="form-group">
						<label for="title" class="col-md-3 control-label"><strong><?php echo $lang_media['Filesize']?></strong></label>
						<div class="col-md-9">
							<div id="filesize"></div>
						</div>			
					</div>

					<div class="form-group">
						<label for="title" class="col-md-3 control-label"><strong><?php echo $lang_media['Uploaded']?></strong></label>
						<div class="col-md-9">
							<div id="posted"></div>
						</div>			
					</div>

					<?php echo active_field(array('name' => 'basename', 'attributes'=>$Module->attributes['basename'], 'desc' => $lang_media['Filename']))?>
					<?php echo active_field(array('name' => 'comment', 'attributes'=>$Module->attributes['comment'], 'desc' => $lang_media['Comment']))?>
				</form>
			</div>
			
			<div class="modal-footer">
				<div class="pull-left">
					<a role="delete" href="#" class="text-danger"><span class="fa fa-times"></span> <?php echo $lang_media['Remove']?></a>
				</div>
				<button role="save" type="button" class="btn btn-primary"><span class="fa fa-floppy-o"></span> <?php echo NFW::i()->lang['Save changes']?></button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo NFW::i()->lang['Close']?></button>
			</div>
		</div>
	</div>
</div>
	
<form id="<?php echo $session_id?>" action="<?php echo NFW::i()->base_path.'media.php?action=upload&session_id='.$session_id?>" method="POST" enctype="multipart/form-data">
	<input type="hidden" name="owner_id" value="<?php echo $owner_id?>" />
	<input type="hidden" name="owner_class" value="<?php echo $owner_class?>" />
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_FILE_SIZE?>" />

	<div id="record-image-template" style="display: none;">
		<div id="%id%" role="record" class="col-xs-6 col-sm-4 col-md-3 col-lg-3">
			<div class="thumbnail">
				<a role="<?php echo $session_id?>-file-properties" href="%url%" data-type="%type%" data-basename="%basename%" data-posted="%posted%" data-filesize="%filesize%">
					<img <?php echo '%tmbsrc%'?> alt="" />
					<span id="comment" style="display: none;">%comment%</span>
				</a>
			</div>
		</div>	
	</div>

	<div id="record-file-template" style="display: none;">
		<div id="%id%" role="record" class="col-xs-6 col-sm-4 col-md-3 col-lg-3">
			<div class="thumbnail">
				<a role="<?php echo $session_id?>-file-properties" href="%url%" data-type="%type%" data-basename="%basename%" data-posted="%posted%" data-filesize="%filesize%">
					<span class="overlay">				
						<img  class="icon" <?php echo '%iconsrc%'?> alt="" />
						<span id="basename" class="basename">%basename%</span>
					</span>
					<img src="<?php echo $icon_container_img?>" alt="" />
					<span id="comment" style="display: none;">%comment%</span>
				</a>
			</div>
		</div>	
	</div>

	<div class="row">
		<div class="col-md-8">
			<div id="dropzone" class="fade well dropzone"><?php echo $lang_media['Messages']['Dropzone']?></div>
			
			<div class="row" id="media-list">
			<?php foreach ($records as $record) { ?>
				<div id="<?php echo $record['id']?>" role="record" class="col-xs-6 col-sm-4 col-md-3 col-lg-3">
					<div class="thumbnail">
						<a role="<?php echo $session_id?>-file-properties" href="<?php echo $record['url']?>" data-type="<?php echo $record['type']?>" data-basename="<?php echo $record['basename']?>" data-posted="<?php echo $record['posted']?>" data-filesize="<?php echo $record['filesize_str']?>">
							<?php if ($record['type'] == 'image'):?>
							<img src="<?php echo tmb($record, $tmb_width, $tmb_height, array('complementary' => true))?>" alt="" />
							<?php else: ?>
							<span class="overlay">				
								<img  class="icon" src="<?php echo $record['icons']['64x64']?>"alt="" />
								<span id="basename" class="basename" title="<?php echo htmlspecialchars($record['basename'])?>"><?php echo htmlspecialchars($record['basename'])?></span>
							</span>
							<img src="<?php echo $icon_container_img?>" alt="" />
							<?php endif;?>
							<span id="comment" style="display: none;"><?php echo htmlspecialchars($record['comment'])?></span>
						</a>
					</div>
				</div>
			<?php } ?>  
			</div>
		</div>
		<div class="col-md-4">
			<div class="alert alert-warning alert-cond alert-dismisable" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<p><?php echo $lang_media['MaxFileSize']?>: <strong><?php echo number_format($MAX_FILE_SIZE/1048576, 2, '.', ' ').$lang_media['mb']?></strong></p>
				<p><?php echo $lang_media['MaxSessionSize']?>: <strong><?php echo number_format($MAX_SESSION_SIZE/1048576, 2, '.', ' ').$lang_media['mb']?></strong></p>
				<p><?php echo $lang_media['CurrentSessionSize']?>: <strong><span id="session-size"><?php echo number_format($session_size/1048576, 2, '.', ' ')?></span><?php echo $lang_media['mb']?></strong></p>
				<?php if ($image_max_x && $image_max_y):?>
				<p><?php echo $lang_media['MaxImageSize']?>: <strong><?php echo $image_max_x.'x'.$image_max_y?>px</strong></p>
				<?php endif; ?>
			</div>
			
			<div class="alert alert-info alert-cond alert-dismisable" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<small><?php echo $lang_media['Messages']['Sorting']?></small>
			</div>

			<label for="<?php echo $session_id?>-upload-button">
      			<span class="btn btn-success btn-lg"><span class="fa fa-folder-open" aria-hidden="true"></span> <?php echo $lang_media['Choose files']?></span>
				<input type="file" name="local_file" id="<?php echo $session_id?>-upload-button" style="display:none" multiple />
			</label>
			<div id="uploading-status" class="uploading-status" style="display: none;"></div>
		</div>
	</div>
</form>