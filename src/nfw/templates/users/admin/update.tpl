<?php
/**
 * @var object $Module
 */
NFW::i()->assign('page_title', $Module->record['username'].' / '.$Module->lang['Title_Edit']);

NFW::i()->breadcrumb = array(
	array('url' => 'admin/users', 'desc' => $Module->lang['Title_Users']),
	array('desc' => $Module->record['username'])
);

NFW::i()->registerResource('jquery.activeForm');
active_field('set_defaults', array('labelCols' => 2, 'inputCols' => 10));

NFW::i()->registerResource('jquery.jgrowl');
?>
<script type="text/javascript">
$(document).ready(function(){
	// Action 'update'
	var f = $('form[id="users-update"]');
	f.activeForm({
		'success': function(response){
			$.jGrowl('<?php echo $Module->lang['Msg_Saved']?>');
		}
	});

	// Action 'update_password'
	var upDialog = $('div[id="update-password-dialog"]');
	upDialog.modal({ 'show': false });

	var upForm = $('form[id="update-password"]');
	upForm.activeForm({
		'success': function(){
			upDialog.modal('hide');
			$.jGrowl('<?php echo $Module->lang['Msg_Pass_updated']?>');
			return false;
		}
	});
	
	$('button[id="update-password"]').click(function(){
		upForm.resetForm().trigger('cleanErrors');
		upForm.find('input[name="password"]').val(randomString(8));
		upDialog.modal('show');
		return false;
	});

	$('button[id="update-password-submit"]').click(function(){
		upForm.submit();
	});

	
	<?php if (NFW::i()->checkPermissions('permissions', 'update')): ?>
	$('div[role="tabpanel"][id="permissions"]').load('<?php echo NFW::i()->base_path.'admin/permissions?action=update&user_id='.$Module->record['id'];?>');
	<?php endif; ?>


	<?php if (NFW::i()->checkPermissions('users', 'delete')): ?>
	// Action 'delete'
	$('[role="users-delete"]').click(function(){
		if (!confirm("<?php echo $Module->lang['Msg_Confirm_delete']?>")) return false;

		$.post('<?php echo $Module->formatURL("delete")?>', { record_id: '<?php echo $Module->record['id']?>' }, function(){
			window.location.href = '<?php echo $Module->formatURL("admin")?>';
		});

		return false;
	});
	<?php endif; ?>
});
</script>

<div id="update-password-dialog" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><?php echo $Module->lang['Update_password']?></h4>
			</div>
			<div id="body" class="modal-body">
				<form id="update-password" action="<?php echo $Module->formatURL('update_password')?>">
					<input type="hidden" name="record_id" value="<?php echo $Module->record['id']?>" />
					<?php echo active_field(array('name' => 'password', 'desc' => $Module->lang['New_password'], 'required'=>true, 'maxlength' => '32'))?>
				</form>
			</div>
			<div class="modal-footer">
				<button id="update-password-submit" type="button" class="btn btn-primary"><span class="fa fa-save"></span> <?php echo NFW::i()->lang['Save changes']?></button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo NFW::i()->lang['Close']?></button>
			</div>
		</div>
	</div>
</div>

<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" class="active"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab"><?php echo $Module->lang['Profile']?></a></li>
	<?php if (NFW::i()->checkPermissions('permissions', 'update')): ?>
		<li role="presentation"><a href="#permissions" aria-controls="permissions" role="tab" data-toggle="tab"><?php echo $Module->lang['Permissions']?></a></li>
	<?php endif; ?>
</ul>
  
<div class="tab-content">
	<div role="tabpanel" class="tab-pane in active" id="profile">
		<br />
		<form id="users-update">
			<?php echo active_field(array('name' => 'username', 'value' => $Module->record['username'], 'attributes'=>$Module->attributes['username']))?>
			<?php echo active_field(array('name' => 'email', 'value' => $Module->record['email'], 'attributes'=>$Module->attributes['email']))?>
			<?php echo active_field(array('name' => 'realname', 'value' => $Module->record['realname'], 'attributes'=>$Module->attributes['realname']))?>
			<?php echo empty($Module->attributes['language']['options']) ? '' : active_field(array('name' => 'language', 'value' => NFW::i()->user['language'], 'attributes'=>$Module->attributes['language']))?>
			<?php echo active_field(array('name' => 'country', 'value' => $Module->record['country'], 'attributes'=>$Module->attributes['country']))?>
			<?php echo active_field(array('name' => 'city', 'value' => $Module->record['city'], 'attributes'=>$Module->attributes['city']))?>
			<?php echo active_field(array('name' => 'group_id', 'value' => $Module->record['group_id'], 'attributes'=>$Module->attributes['group_id']))?>
			<?php echo active_field(array('name' => 'is_blocked', 'value' => $Module->record['is_blocked'], 'attributes'=>$Module->attributes['is_blocked']))?>
			
			<div class="form-group">
				<div class="col-md-10 col-md-offset-2">
					<?php if (NFW::i()->checkPermissions('users', 'delete')): ?>
					<div class="pull-right">
						<a role="users-delete" href="#" class="text-danger"><span class="fa fa-times"></span> <?php echo $Module->lang['Delete']?></a>
					</div>
					<?php endif; ?>

					<button type="submit" class="btn btn-primary"><span class="fa fa-save"></span> <?php echo NFW::i()->lang['Save changes']?></button>
					
	        		<?php if (NFW::i()->checkPermissions('users', 'update_password')): ?>
	        		<button id="update-password" class="btn btn-default"><?php echo $Module->lang['Update_password']?></button>
	        		<?php endif; ?>
				</div>
			</div>
		</form>	
	</div>
	
	<div role="tabpanel" class="tab-pane" id="permissions"></div>
</div>