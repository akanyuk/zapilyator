<?php
/**
 * @var object $Module
 */
NFW::i()->registerResource('jquery.activeForm');
NFW::i()->registerResource('jquery.jgrowl');
?>
<script type="text/javascript">
$(document).ready(function(){
	// Action 'update'
	var f = $('form[id="profile-update"]');
	f.activeForm({
		'success': function(response){
			if (response.is_updated) {
				$.jGrowl('<?php echo $Module->lang['Msg_Saved']?>');
			}
		}
	});
});
</script>

<form id="profile-update">
	<fieldset>
	    <legend>Параметры профиля</legend>

		<div class="form-group">
			<label class="col-md-3 control-label"><?php echo $Module->lang['Username']?></label>
			<div class="col-md-9"><p class="form-control-static"><?php echo htmlspecialchars(NFW::i()->user['username'])?></p></div>
		</div>
		
		<div class="form-group">
			<label class="col-md-3 control-label">E-mail</label>
			<div class="col-md-9"><p class="form-control-static"><?php echo NFW::i()->user['email']?></p></div>
		</div>

		<?php echo active_field(array('name' => 'realname', 'value' => NFW::i()->user['realname'], 'attributes'=>$Module->attributes['realname']))?>
		<?php echo empty($Module->attributes['language']['options']) ? '' : active_field(array('name' => 'language', 'value' => NFW::i()->user['language'], 'attributes'=>$Module->attributes['language']))?>
		<?php echo active_field(array('name' => 'country', 'value' => NFW::i()->user['country'], 'attributes'=>$Module->attributes['country']))?>
		<?php echo active_field(array('name' => 'city', 'value' => NFW::i()->user['city'], 'attributes'=>$Module->attributes['city']))?>

		<?php echo active_field(array('name' => 'password', 'type' => 'password', 'desc'=>'Новый пароль', 'maxlength' => '32'))?>
		<?php echo active_field(array('name' => 'password2', 'type' => 'password', 'desc'=>'Повторите ввод', 'maxlength' => '32'))?>

		<div class="form-group">
			<div class="col-md-9 col-md-offset-3">
				<div class="alert alert-info">Если Вы не хотите менять пароль, оставьте оба поля пустым.</div>
				
				<button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> <?php echo NFW::i()->lang['Save changes']?></button>
			</div>
		</div>
	</fieldset>
</form>