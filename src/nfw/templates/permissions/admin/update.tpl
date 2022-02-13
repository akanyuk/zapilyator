<?php
/**
 * @var object $Module
 * @var array $user
 * @var array $group_roles
 * @var array $user_roles
 */
reset($user);
$lang_permissions = NFW::i()->getLang('permissions');
?>
<script type="text/javascript">
$(document).ready(function(){
	
	// Update roles
	$('form[id="update-roles"]').activeForm({
		'success': function(response){
			$.jGrowl('<?php echo $lang_permissions['Success']?>');
		}
	});
});
</script>
<br />
<form id="update-roles" action="<?php echo $Module->formatURL('update').'&user_id='.$user['id']?>">
	<input type="hidden" name="form_sent" value="1" />

		<table class="table table-striped table-hover">	
			<thead>
				<tr>
					<th>Group</th>
					<th>User</th>
					<th style="width: 100%;"><?php echo $lang_permissions['Role rules']?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($Module->roles as $role_name=>$role) { ?>
				<tr>
					<td>
						<label class="checkbox-inline">
							<input type="checkbox" disabled="disabled" title="Role from user's group" <?php if (in_array($role_name, $group_roles)) echo 'checked="checked"' ?> />
							&nbsp;
						</label>
					</td>
					<td style="white-space: nowrap;">
						<label class="checkbox-inline">
							<input type="checkbox" name="roles[<?php echo $role_name?>]" <?php if (in_array($role_name, $user_roles)) echo 'checked="checked"' ?> />
							<?php echo $role_name?>
						</label>
					</td>
					<td>
						<?php foreach ($role as $r) { ?>
		            		<div><small><?php echo htmlspecialchars($r['description'])?></small></div>
		            	<?php } ?>
					</td>
				</tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2"></td>
					<td>
						<button type="submit" class="btn btn-primary"><span class="fa fa-save"></span> <?php echo $lang_permissions['Save changes']?></button>		
					</td>
				</tr>
			</tfoot>
		</table>
</form>