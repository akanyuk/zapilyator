<?php 
	class ui_dialog {
		private $dialog_id = 'ui-dialog';
		
		function __construct() {
			$this->dialog_id .= '-'.substr(md5(time()), 24);
		}
		function getID() {
			return $this->dialog_id;						
		}		
		
		function render($options = array()) {
			$lang_main = NFW::i()->getLang('nfw_main');
			
			$title = isset($options['title']) && $options['title'] ? $options['title'] : $lang_main['Message'];
?>
<script type="text/javascript">
$(document).ready(function(){
	// Success dialog
	var uiDialog = $('div[id="<?php echo $this->dialog_id?>"]');
	uiDialog.appendTo('body');	// Move dialog to end of page
	uiDialog.modal({ 'show': false });

	$(document).on('show-<?php echo $this->dialog_id?>', function(e, message){
		uiDialog.find('div[id="body"]').html(message);
		uiDialog.modal('show');
	});
	uiDialog.on('hidden.bs.modal', function (e) {
		$(document).trigger('hide-<?php echo $this->dialog_id?>');
	})	
});
</script>
<div id="<?php echo $this->dialog_id?>" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><?php echo $title?></h4>
			</div>
			<div id="body" class="modal-body"></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang_main['Close']?></button>
			</div>
		</div>
	</div>
</div>
<?php
		} 
	}