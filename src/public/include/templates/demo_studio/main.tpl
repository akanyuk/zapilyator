<?php
	NFW::i()->registerResource('jquery.activeForm');
	NFW::i()->registerResource('jqueryui.interactions');
?>
<script type="text/javascript">
$(document).ready(function() {
	$('div[class~="navbar"] div[class="container"]').prepend('<div class="pull-right" style="padding-top: 10px;">Project key: <strong><?php echo $Module->project_key?></strong> &nbsp;&nbsp;<button id="close-project" class="btn btn-danger btn-xs" title="Close project"><span class="glyphicon glyphicon-remove"></span></button></div>');
	
	$('button[id="close-project"]').click(function(){
		if (!confirm('Close current project?\nIf you forgot project key, you can not reopen project!')) return false;
		$.post('?action=close_project', function(){
			window.location.reload();
		});
	});

	
	$('form[id="save-settings"]').activeForm({
		'success': function(response){
			$('div[id="success-dialog"]').find('div[id="message"]').html(response.message);
			$('div[id="success-dialog"]').modal('show').on('hide.bs.modal', function () {
					// window.location.reload();					
			});
		}
	});

	// Drag resource from project
	$('*[id="system-resource"]').draggable({
		helper: 'clone'
	});
	
	$('*[rel="flow-container"]').droppable({
		hoverClass: "state-hover",
		drop: function(event, ui) {
			if (ui.draggable.attr('id') == 'system-resource') { 
				var text = ui.draggable.text();
				var title = ui.draggable.attr('title');
				
				var item = item = $('div[id="flow-item-template"]').html();
				item = item.replace('%heading%', text);
				item = item.replace('%title%', title);
				item = item.replace('%body%', 'body text');
				$(item).appendTo(this);
			}
		}
	});

 	// Sortable `flows`
	$('*[rel="flow-container"]').sortable({
		connectWith: '*[rel="flow-container"]',
		handle: '.panel-heading',
		placeholder: 'flow-item-placeholder',
		cursor: 'move',
	});

	// Remove resource
	$(document).on('click', 'button[id="flow-item-remove"]', function(){
		$(this).closest('div[id="flow-item"]').remove();
	});
	
});
</script>
<style>
	/* Project accordion menu */
	#pm .panel { font-size: 85%; }
	#pm H4 { font-size: 110%; }
	#pm .panel-heading, #pm .list-group-item { padding: 8px 10px; }
	#pm .panel-body { padding: 10px; }
	#pm .panel input[type="file"] { max-width: 190px; }
	#pm .help-block { margin-top: 0; margin-bottom: 0; }

	/* Timeline */
	#flow-item { font-size: 85%; margin-bottom: 8px; }
	#flow-item #drag { color: #000; opacity: 0.2; }
	#flow-item .close { font-size: 15px; }
	#flow-item #drag:focus, #flow-item #drag:hover { cursor: pointer; opacity: 0.5; }
	#flow-item .panel-heading { padding: 3px 5px }
	#flow-item .panel-body { padding: 4px 8px }
	
	.state-hover { border: 1px dotted #eea; }
	.flow-item-placeholder { border: 1px dotted #777; border-radius: 4px; height: 50px; margin-bottom: 8px; }
</style>

<div id="flow-item-template" style="display: none;">
<div id="flow-item" class="panel panel-warning">
	<div class="panel-heading" title="%title%">
		<button id="flow-item-remove" type="button" class="close"><span aria-hidden="true">&times;</span></button>
		%heading%
	</div>
	<div class="panel-body">%body%</div>
</div>
</div>

<div id="success-dialog" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">Success</h4>
			</div>
			<div id="content" class="modal-body"><div id="message"></div></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<div class="row"><div class="col-md-3">

<div class="panel-group" id="pm">

<div class="panel panel-default"><div class="panel-heading"><h4 class="panel-title"><a data-toggle="collapse" data-parent="#pm" href="#systemResources">System resources</a></h4></div>
<div id="systemResources" class="panel-collapse collapse in"><div class="panel-body">

<div class="list-group"><?php foreach ($Module->resources as $key=>$r) { ?>
	<a id="system-resource" href="#" class="list-group-item" title="<?php echo $r['description']?>"><?php echo $r['name']?></a>
<?php } ?></div>

</div></div></div>

<div class="panel panel-default"><div class="panel-heading"><h4 class="panel-title"><a data-toggle="collapse" data-parent="#pm" href="#projectResources">Project resources</a></h4></div>
<div id="projectResources" class="panel-collapse collapse"><div class="panel-body">
	<form id="upload-resource" action="?action=upload_resource" enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="262144" />
		
		<fieldset>
			<legend>Upload resource</legend>
			
			<div class="form-group" id="resource_file">
				<input type="file" name="resource_file" />
				<span class="help-block"></span>
			</div>
			
			<button type="submit" class="btn btn-primary btn-sm">Upload</button>
		</fieldset>
	</form>
</div></div></div>
			
<div class="panel panel-default"><div class="panel-heading"><h4 class="panel-title"><a data-toggle="collapse" data-parent="#pm" href="#projectSettings">Project settings</a></h4></div>
<div id="projectSettings" class="panel-collapse collapse"><div class="panel-body">
	<form id="save-settings" action="?action=save_settings">
	
		<div class="form-group" id="project_name">
			<label for="project_name"><strong><?php echo $Module->attributes['project_name']['desc']?></strong></label>
			<input type="text" name="project_name" value="<?php echo $Module->project_settings['project_name']?>" maxlength="<?php echo $Module->attributes['project_name']['maxlength']?>" class="form-control input-sm" />
			<span class="help-block"></span>
		</div>

		<div class="form-group" id="project_desc">
			<label for="project_desc"><strong><?php echo $Module->attributes['project_desc']['desc']?></strong></label>
			<input type="text" name="project_desc" value="<?php echo $Module->project_settings['project_desc']?>" maxlength="<?php echo $Module->attributes['project_desc']['maxlength']?>" class="form-control input-sm" />
			<span class="help-block"></span>
		</div>

		<div class="form-group" id="project_author">
			<label for="project_author"><strong><?php echo $Module->attributes['project_author']['desc']?></strong></label>
			<input type="text" name="project_author" value="<?php echo $Module->project_settings['project_author']?>" maxlength="<?php echo $Module->attributes['project_author']['maxlength']?>" class="form-control input-sm" />
			<span class="help-block"></span>
		</div>

		<button type="submit" class="btn btn-primary btn-sm">Save settings</button>
	</form>					
</div></div></div>
		
</div></div> <!--  end of collapse  -->

<div class="col-md-9"><div class="row">
	
	<div class="col-md-3">
		<p class="bg-primary" style="padding: 5px 15px;">main flow</p>
		<div rel="flow-container" class="list-group" style="min-height: 500px;"></div>
	</div>
	<div class="col-md-3">
		<p class="bg-primary" style="padding: 5px 15px;">int1 flow</p>
		<div rel="flow-container" class="list-group" style="min-height: 500px;"></div>
	</div>
	<div class="col-md-3">
		<p class="bg-primary" style="padding: 5px 15px;">int2 flow</p>
		<div rel="flow-container" class="list-group" style="min-height: 500px;"></div>
	</div>
	<div class="col-md-3">
		<p class="bg-primary" style="padding: 5px 15px;">music flow</p>
		<div rel="flow-container" class="list-group" style="min-height: 500px;"></div>
	</div>
	
</div></div>

</div> <!--  end of row  -->