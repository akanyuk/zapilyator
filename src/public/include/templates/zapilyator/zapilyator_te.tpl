<?php
	NFW::i()->registerResource('jquery.activeForm');
	NFW::i()->registerResource('bootstrap.zxcolorselector');
	NFW::i()->registerResource('bootstrap.slider');
	
	$max_pattern = 64;
	
/*	
	$speeds = array(
		array('value' => 0, 'desc' => 'Take from GIF'),
		array('value' => 1, 'desc' => '1 - fastest'),
		2,3,4,5,6,7,8,9,10,12,15,20,30,40,50,75,100,125,150,200,255
	);
*/	
	$button_labels = array(
		'Make demo not war',
		'Make demo not war',
		'Make demo not war',
		'Please sit back and relax!',
		'Please sit back and relax!',
		'Please sit back and relax!',
		'Zapili mne pobyromu!',
		'Zapili mne pobyromu!',
		'I accept the the license agreement',
		'Are you ready?',
		'PILI@DOMINATE!'
	);
	
	$effects = array(
		'setBorder' => array(
			'title' => 'Set screen border'
		),		
	);
?>
<script type="text/javascript">
$(document).ready(function() {
	$.blockUI.defaults.timeout = 1;

	// Close all opened popovers
	$('body').on('click', function (e) {
	    $('[data-toggle="popover"]').each(function () {
	        if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
	            $(this).popover('hide');
	        }
	    });
	});
	
	// ZX Colorpicker
	$('[data-type="zxcolorselector"]').zxcolorselector();
			
//	var aEffects = [];
	<?php foreach ($effects as $label => $e) { ?>
//	aEffects.push({ 'label': '<?php echo $label?>', 'title': '<?php echo $e['title']?>' });
	<?php } ?>

	
	// Adding resource
	var aD = $('div[id="resource-add-dialog"]');
	
	$('button[name="resource-add"]').click(function(e){
		aD.data('flow', $(this).attr('id')).modal('show');
		e.preventDefault();
	});

	$('button[id="resource-add-done"]').click(function(){
		var container = $('div[id="' + aD.data('flow') + '-container"]');

		var label = aD.find('select[name="effect"]').val();
		var title = aD.find('select[name="effect"]').text();

		var item = $('div[id="flow-item-template"]').html();
		item = item.replace('%flow%', aD.data('flow'));
		item = item.replace('%title%', title);
		item = item.replace('%body%', 'body text');

		$(item).appendTo(container);
		fixIntFlow();

		// Customize controls
		
		$('[data-toggle="popover"]').popover({
			'container': 'body', 'placement': 'top', 'html': true,
			'title': function(){
				var content = $(this).data('title') + '<button id="popover-close" class="close">&times;</button>';
				return content;
			},
			'content': function(){
				var content = $('div[id="popover-content"]').find('div[id="' + $(this).attr('id') + '"]').html().replace('%value%', $(this).text());
				return content;
			}
		}).on('shown.bs.popover', function () {
			var owner = $(this);
			var value = owner.text();
			var popover = $('[id="' + $(this).attr('aria-describedby') + '"]');

			if (owner.prop('id') == 'run_after') {
				popover.find('select').html('<option value="254"' + (value == 'nop' ? ' selected="selected"' : '') + '>nop</option>');
				$('div[id="int-flow-container"]').find('div[id="flow-item"]').each(function(i, o){
					var curTitle = $(this).find('span[id="title"]').text();
					popover.find('select').append('<option value="' + i + '"' + (value == i ? ' selected="selected"' : '') + '>' + i + '. ' + curTitle + '</option>');
				});				
			}
			else if (popover.find('[data-type="slider"]').length) {
				if (popover.find('[data-type="slider"]').data('slider-value') == 'nop') {
					popover.find('[data-type="slider"]').attr('data-slider-value', '-1');
				}
				
				popover.find('[data-type="slider"]').slider({
					tooltip: 'always',
					formatter: function(value) {
						value = value == -1 ? 'nop' : value;
						owner.text(value);
						return value;
					}
				});
			}
 
		});
				
		aD.modal('hide');
	});
	
	$(document).on('click', '[id="popover-input-done"]', function(){
		var owner = $('[aria-describedby="' + $(this).closest('.popover').attr('id') + '"]');
		owner.text($(this).siblings('input').val()).popover('hide');
	});

	$(document).on('click', '[id="popover-close"]', function(){
		var owner = $('[aria-describedby="' + $(this).closest('.popover').attr('id') + '"]');
		owner.popover('hide');
	});
	
	
	// Remove resource from flow
	$(document).on('click', 'button[id="flow-item-remove"]', function(){
		$(this).closest('div[id="flow-item"]').remove();
		fixIntFlow();
	});

	
	// TEST ONLY!!!
	$('button[id="resource-add-done"]').trigger('click').trigger('click');

	
	// Make
		
	$('button[id="m-d-n-w"]').click(function(){
		$('div[id="result-log"]').html('<div>Loading data...</div>');
		$('button[id="download-result"], button[id="close-dialog"]').attr('disabled', 'disabled');
		$('div[id="result-dialog"]').modal('show');
	});

	$('button[id="download-result"]').click(function(){
		window.location.href = $(this).attr('href');
	});
	
	$('form[id="demo-maker"]').activeForm({
		'error': function(response){
			handleResponse(response);
		},
		'success': function(response){
			handleResponse(response);
		}
	});



	
	// Fix counters & run_after list in int flow
	function fixIntFlow() {
		$('div[id="int-flow-container"]').find('div[id="flow-item"]').each(function(i, o){
			$(this).find('span[id="counter"]').text(i + '. ');
		});
	}
	
	function handleResponse(response) {
		if (response.result == 'error') {
			$('div[id="result-log"]').append('<div class="error">' + response.last_msg + '</div>');
			$('button[id="close-dialog"]').removeAttr('disabled');
			return;
		}
		else if (response.result == 'done') {
			// Append success messages
			$.each(response.log, function(i, text){
				$('div[id="result-log"]').append('<div>' + text + '</div>');
			});
			$('div[id="result-log"]').scrollTop(9999);
		
			
			$('button[id="download-result"], button[id="close-dialog"]').removeAttr('disabled');
			$('button[id="download-result"]').attr('href', response.download);
			return;
		}

		// Append log
		$.each(response.log, function(i, text){
			$('div[id="result-log"]').append('<div>' + text + '</div>');
		});
		$('div[id="result-log"]').scrollTop(9999);
		
		// Do next stage
		$.post(null, response, function(next_response){
			handleResponse(next_response);
		}, 'json');
	}
});
</script>

<style>
	div#result-log { font-family: monospace; font-size: 85%; white-space: pre-wrap; height: 256px; max-height: 256px; overflow: scroll; }
	div#result-log .error { font-weight: bold; color: red; }
	div#result-log .success { font-weight: bold; color: green; }
	
	/* flow items */
	#flow-item { margin-bottom: 10px; }
	#flow-item .close { font-size: 15px; }
	#flow-item .panel-heading { padding: 3px 5px }
	#flow-item .panel-body { padding: 4px 8px }
	
	#flow-item #counter { font-weight: bold; }
	
	#flow-item .panel-footer { padding: 1px 5px; }
	#flow-item .status { display: inline; padding-right: 10px; }
	#flow-item .status .desc { font-size: 80%; display: inline;  }	
	#flow-item .status .val { font-size: 80%; display: inline; font-weight: bold; cursor: pointer; }
	
	/* parameters popover */	
	.input-group-addon { cursor: pointer}
</style>

<div id="flow-item-template" style="display: none;">
	<div id="flow-item" class="panel panel-primary">
		<div id="flow" style="display: none;">%flow%</div>
		
		<div class="panel-heading">
			<button id="flow-item-remove" type="button" class="close"><span aria-hidden="true">&times;</span></button>
			<span id="counter"></span><span id="title">%title%</span>
		</div>
		<div class="panel-body">%body%</div>
  		<div class="panel-footer">
  			<div class="status" title="Start pattern">
  				<div class="desc">s:</div>
  				<div class="val" data-toggle="popover" id="start_pattern" data-title="Start pattern">nop</div>
  			</div>
  			<div class="status" title="Stop pattern">
  				<div class="desc">e:</div>
  				<div class="val" data-toggle="popover" id="stop_pattern" data-title="Stop pattern">nop</div>
  			</div>
  			<div class="status" title="Duration (ints)">
  				<div class="desc">d:</div><div class="val" data-toggle="popover" id="duration" data-title="Duration (ints)">65535</div>
  			</div>
  			<div class="status" title="Run after resource/effect">
  				<div class="desc">a:</div><div class="val" data-toggle="popover" id="run_after" data-title="Run after resource/effect">nop</div>
  			</div>
  		</div>		
	</div>
</div>

<div id="result-dialog" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header"><h4 class="modal-title">Result</h4></div>
			<div id="content" class="modal-body"><div id="result-log"></div></div>
			<div class="modal-footer">
				<button id="download-result" class="btn btn-primary" disabled="disabled">Download</button>
				<button id="close-dialog" type="button" class="btn btn-default" data-dismiss="modal" disabled="disabled">Close</button>
			</div>
		</div>
	</div>
</div>

<div id="resource-add-dialog" class="modal fade" data-flow="int-flow">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Add resource/effect to project</h4>
			</div>
			<div id="content" class="modal-body">
				<select name="effect" class="form-control">
					<?php foreach ($effects as $label => $e) { ?>
						<option value="<?php echo $label?>"><?php echo $e['title']?></option>
					<?php } ?>
				</select>
			</div>
			<div class="modal-footer">
				<button id="resource-add-done" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span> Add</button>
			</div>
		</div>
	</div>
</div>

<div id="popover-content" style="display: none;">
	<div id="start_pattern">
		<div class="input-group">
			<br />
			<input type="text" data-type="slider" data-slider-min="-1" data-slider-max="<?php echo $max_pattern?>" data-slider-step="1" data-slider-value="%value%" />
		</div>
	</div>

	<div id="stop_pattern">
		<div class="input-group">
			<br />
			<input type="text" data-type="slider" data-slider-min="-1" data-slider-max="<?php echo $max_pattern?>" data-slider-step="1" data-slider-value="%value%" />
		</div>
	</div>
	
	<div id="duration">
		<div class="input-group">
			<input type="text" value="%value%" class="form-control" placeholder="Duration 1-65535" />
			<div id="popover-input-done" class="input-group-addon">OK</div>
		</div>
	</div>
	
	<div id="run_after">
		<select class="form-control"></select>
	</div>
</div>



<form id="demo-maker" action="?action=make" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="16777216" />
	
	<div class="row">
		<div class="col-md-4">
			<fieldset>
				<legend>Main flow</legend>
				<div id="main-flow-container"></div>
			</fieldset>
		</div>
		<div class="col-md-4">
			<fieldset>
				<legend>Int flow</legend>
			</fieldset>
			<div id="int-flow-container"></div>
		</div>
		
		<div class="col-md-4">
			<fieldset>
				<legend>Global</legend>
				
				<div class="form-group">
					<label class="control-label">PT2/PT3 music</label>
					<input type="file" name="music_file" />
					<div class="help-block"></div>
				</div>	
				
				<div class="form-group">
					<div class="row">
						<div class="col-md-4"><label class="control-label">BORDER</label></div>
						<div class="col-md-8"><div id="main_border" data-type="zxcolorselector">0</div></div>
					</div>
				</div>
				
				<div class="form-group">
					<div class="row">
						<div class="col-md-4"><label class="control-label">PAPER / INK</label></div>
						<div class="col-md-8"><div id="main_color" data-type="zxcolorselector" data-selector="full">71</div></div>
					</div>
				</div>
			</fieldset>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4" style="padding-left: 50px;"><button name="resource-add" id="main-flow" class="btn btn-default">Add resource/effect to main flow</button></div>
		<div class="col-md-4" style="padding-left: 50px;"><button name="resource-add" id="int-flow" class="btn btn-default">Add resource/effect to int flow</button></div>
	</div>
	<hr />
	<div class="row">	
		<div class="col-md-10 col-md-offset-2">
			<button id="m-d-n-w" type="submit" class="btn btn-primary btn-lg"><?php echo $button_labels[array_rand($button_labels)]?></button>
		</div>
	</div>
</form>