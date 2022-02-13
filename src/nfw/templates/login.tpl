<?php
/**
 * @var array $login_options
 */
NFW::i()->registerResource('bootstrap');
NFW::i()->registerResource('jquery.activeForm');

$login_options = isset($login_options) && is_array($login_options) ? $login_options : array();
?>
<!DOCTYPE html>
<html><head>
<title><?php echo NFW::i()->lang['Authorization']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript">
$(document).ready(function(){
	var f = $('form[id="login"]');
	f.activeForm({
		success: function(response) {
			<?php if (isset($login_options['redirect']) && $login_options['redirect']):?>
			window.location.href = '<?php echo $login_options['redirect']?>';
			<?php else: ?>
			if (response.redirect) {
				window.location.href = response.redirect;
			}
			else {
				window.location.reload();
			}
			<?php endif; ?>
		}
	});
});
</script>
</head>
<body>
<div id="page-content" class="container">
	<form id="login">
		<fieldset>
			<legend><?php echo NFW::i()->lang['Authorization']?></legend>
			<div class="alert alert-warning"><?php echo NFW::i()->lang['Authorization_desc']?></div>
			
			<?php echo active_field(array('name' => 'username', 'desc'=> NFW::i()->lang['Login'], 'labelCols' => 1, 'inputCols' => 3))?>
			<?php echo active_field(array('name' => 'password', 'type' => 'password', 'desc'=> NFW::i()->lang['Password'], 'labelCols' => 1, 'inputCols' => 3))?>
			
			<div class="form-group">
				<div class="col-md-9 col-md-offset-1">
					<button name="login" class="btn btn-primary" type="submit"><?php echo NFW::i()->lang['GoIn']?></button>
				</div>
			</div>
		</fieldset>	
    </form>
</div>
</body></html>	