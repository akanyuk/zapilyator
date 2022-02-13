<?php
/**
 * @var object $Module
 * @var array $records
 */
NFW::i()->assign('page_title', $Module->lang['Title_Users'].' / '.$Module->lang['Title_List']);

NFW::i()->registerResource('dataTables');
NFW::i()->registerResource('dataTables/Scroller');
NFW::i()->registerResource('jquery.activeForm');
?>
<script type="text/javascript">
$(document).ready(function(){
	// Action 'admin'
	var config =  dataTablesDefaultConfig;
	
	// Infinity scrolling
	config.scrollY = $(window).height() - $('table[id="users"]').offset().top - 130;
	config.deferRender = true;
	config.scroller = true;
	
	// Create columns
	config.columns = [
		{ 'orderable': false, 'visible': false },				// Group ID
		{ 'orderable': false, 'searchable': false, 'className': 'icon-column' },	// is_blocked
	    { },											// Username
	    { },											// Realname
	    { },											// City / Country
	    { 'className': 'nowrap' },						// E-Mail
	    { 'className': 'nowrap' },						// Registered
	    { 'orderable': false, 'className': 'nowrap' }	// Registered IP
    ];

	config.order = [[1,'desc']];
	config.search = { 'search': '<?php echo (isset($_GET['filter'])) ? htmlspecialchars($_GET['filter']) : ''?>' };

	config.rowCallback = function(row, data, index) {
		// Status icons
		if (data[1] == 1) {
			$('td:eq(0)', row).html('<span class="fa fa-user-lock" title="<?php echo $Module->lang['Status']['Blocked']?>"></span>');
		}
		else {
			$('td:eq(0)', row).html('<span class="fa fa-check" title="<?php echo $Module->lang['Status']['Active']?>"></span>');
		}

		// Dates
		$('td:eq(5)', row).html(formatDateTime(data[6]));
	};

	var oTable = $('table[id="users"]').dataTable(config);

	// Custom filtering function
	$('div[id="users_length"]').empty().html($('div[id="custom-filters"]').html());
	$('div[id="custom-filters"]').remove();

	// Custom filtering
	$.fn.dataTableExt.afnFiltering.push(
		function(settings, data, index) {
			var isFiltered = false;

			var group_id = $('select[id="group_id"] option:selected').val();
			if (group_id == '-1' || group_id == data[0]) {
				isFiltered = true;
			}

			return isFiltered;
		}
	);

	$('select[id="group_id"]').change(function(){
		oTable.fnDraw();
	});


	// Action 'insert
	var insertDialog = $('div[id="users-insert-dialog"]');
	insertDialog.modal({ 'show': false });

	$(document).on('click', 'button[id="users-insert"]', function(e, message){
		$('form[id="users-insert"]').resetForm().trigger('cleanErrors');
		$('form[id="users-insert"]').find('input[name="password"]').val(randomString(8));
		insertDialog.modal('show');
	});

	$('form[id="users-insert"]').activeForm({
		'success': function(response) {
			insertDialog.modal('hide');
			window.location.href = '<?php echo $Module->formatURL('update')?>&record_id=' + response.record_id;
			return false;
		}
	});
	
	$('button[id="users-insert-submit"]').click(function(){
		$('form[id="users-insert"]').submit();
	});

	$(document).on('click', 'a[role="ip2geo"]', function(){
		if ($(this).data('ip2geo-loaded') == 'success') {
		    $(this).popover('toggle');
		    return;
		}

		var selector = $(this);

		$.get('<?php echo $Module->formatURL('ip2geo')?>&ip=' + $(this).data('ip'), function(response){
			var content = response.result == 'success' ? '<div>' + response.city + '</div><div>' + response.region + '</div><div>' + response.country + '</div>' : 'Unknown geo location';

			selector.popover({
				'container': 'body',
				'placement': 'left',
				'trigger': 'focus',
				'html': true,
				'content': content
			});
						
			selector.popover('show');
			selector.data("ip2geo-loaded", "success");
		}, 'json');
		
		return false;
	});
});
</script>

<?php if (NFW::i()->checkPermissions('users', 'insert')) : ?>
<div id="users-insert-dialog" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><?php echo $Module->lang['New']?></h4>
			</div>
			<div id="body" class="modal-body">
				<form id="users-insert" action="<?php echo $Module->formatURL('insert')?>">
					<?php echo active_field(array('name' => 'username', 'attributes'=>$Module->attributes['username']))?>
					<?php echo active_field(array('name' => 'password', 'name'=>"password", 'desc'=>"Пароль", 'required'=>true, 'maxlength'=>32))?>
					<?php echo active_field(array('name' => 'email', 'attributes'=>$Module->attributes['email']))?>
					<?php echo active_field(array('name' => 'realname', 'attributes'=>$Module->attributes['realname']))?>

					<?php echo isset(NFW::i()->cfg['available_languages']) && !empty(NFW::i()->cfg['available_languages']) ? active_field(array('name' => 'language', 'attributes'=>$Module->attributes['language'])) : ''?>
					
					<?php echo active_field(array('name' => 'country', 'attributes'=>$Module->attributes['country']))?>
			    	<?php echo active_field(array('name' => 'city', 'attributes'=>$Module->attributes['city']))?>
					<?php echo active_field(array('name' => 'group_id', 'attributes'=>$Module->attributes['group_id']))?>
				</form>
			</div>
			<div class="modal-footer">
				<button id="users-insert-submit" type="button" class="btn btn-primary"><span class="fa fa-save"></span> <?php echo NFW::i()->lang['Save changes']?></button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo NFW::i()->lang['Close']?></button>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<div id="custom-filters" style="display: none;">
	<div style="float: left;">
		<?php if (NFW::i()->checkPermissions('users', 'insert')) : ?>
		<button id="users-insert" class="btn btn-primary"><span class="fa fa-user-plus"></span> <?php echo $Module->lang['New']?></button>
		<?php endif; ?>
		
		<select id="group_id" class="form-control" style="width: 200px;">
			<option value="-1"><?php echo $Module->lang['All']?></option>
			<?php foreach ($Module->attributes['group_id']['options'] as $i) { ?>
				<option value="<?php echo $i['id']?>"><?php echo htmlspecialchars($i['desc'])?></option>
			<?php } ?>
		</select>
	</div>
</div>
<table id="users" class="table table-striped">
	<thead>
		<tr>
			<th>group_id</th>
			<th></th>
			<th><?php echo $Module->lang['Username']?></th>
			<th><?php echo $Module->lang['Realname']?></th>
			<th><?php echo $Module->lang['Attributes']['country']?></th>
			<th>E-mail</th>
			<th><?php echo $Module->lang['Registered']?></th>
			<th>IP</th>
		</tr>
	</thead>
	<tbody>
<?php 
	foreach ($records as $record) {
		$location = array();
		if ($record['city']) $location[] = $record['city']; 
		if ($record['country']) $location[] = $record['country'];
?>
			<tr>
				<td><?php echo $record['group_id']?></td>
				<td><?php echo $record['is_blocked']?></td>
				<td><?php echo NFW::i()->checkPermissions('users', 'update') ? '<a href="'.$Module->formatURL('update').'&record_id='.$record['id'].'">'.htmlspecialchars($record['username']).'</a>' : htmlspecialchars($record['username'])?></td>
				<td><?php echo htmlspecialchars($record['realname']); ?></td>
				<td><?php echo htmlspecialchars(implode(' / ', $location)); ?></td>
				<td><?php echo htmlspecialchars($record['email']); ?></td>
				<td><?php echo $record['registered']?></td>
				<td><?php echo $record['registration_ip']?> <a href="#" role="ip2geo" data-ip="<?php echo $record['registration_ip']?>"><span class="glyphicon glyphicon-question-sign"></span></a></td>
			</tr>
		<?php } ?>
</tbody></table>