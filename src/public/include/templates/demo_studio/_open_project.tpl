<?php
	NFW::i()->registerResource('jquery.activeForm');
?>
<script type="text/javascript">
$(document).ready(function() {
	$('form[id="load-project"], form[id="create-project"]').activeForm({
		'success': function(response){
			if (response.message) {
				$('div[id="success-dialog"]').find('div[id="message"]').html(response.message);
				$('div[id="success-dialog"]').modal('show').on('hide.bs.modal', function () {
					window.location.reload();					
				});
			}
			
			window.location.reload();
		}
	});
});
</script>
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

<form id="load-project" class="form-horizontal" action="?action=open_project">
	<fieldset>
		<legend>Load exist project</legend>
		<?php echo active_field(array('name' => 'project_key', 'desc' => 'Project key', 'type' => 'str', 'required' => true, 'minlength' => 8, 'maxlength' => 8)) ?>
		
		<div class="form-group">
			<div class="col-md-offset-3 col-md-9">
				<button type="submit" class="btn btn-primary">Load project</button>
			</div>
		</div>
	</fieldset>
</form>
<form id="create-project" class="form-horizontal" action="?action=create_project">
	<fieldset>
		<legend>Create new project</legend>
		<?php echo active_field(array('name' => 'project_name', 'attributes' => $Module->attributes['project_name'])) ?>
		<?php echo active_field(array('name' => 'project_desc', 'attributes' => $Module->attributes['project_desc'])) ?>
		<?php echo active_field(array('name' => 'project_author', 'attributes' => $Module->attributes['project_author'])) ?>
		
		<div class="form-group">
			<div class="col-md-offset-3 col-md-9">
				<button type="submit" class="btn btn-primary">Create project</button>
			</div>
		</div>
	</fieldset>
</form>