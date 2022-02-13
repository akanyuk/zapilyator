<?php
	// Demo maker special edition: nodeus animations
	NFW::i()->registerResource('jquery.activeForm');
	
	$speeds = array(
		array('value' => 0, 'desc' => 'Take from GIF'),
		array('value' => 1, 'desc' => '1 - fastest'),
		2,3,4,5,6,7,8,9,10,12,15,20,30,40,50,75,100,125,150,200,255
	);
?>
<script type="text/javascript">
$(document).ready(function() {
	$.blockUI.defaults.timeout = 1;

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
	form#demo-maker .form-group-margin { margin-bottom: 10px; }
	
	div#result-log { font-family: monospace; font-size: 85%; white-space: pre-wrap; height: 256px; max-height: 256px; overflow: scroll; }
	div#result-log .error { font-weight: bold; color: red; }
	div#result-log .success { font-weight: bold; color: green; }
</style>

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

<h1><span class="pull-right label label-danger">DISCONTINUED!</span>Zapilyator ND edition</h1>
<hr />
<form id="demo-maker" class="form-horizontal" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="16777216" />
	<fieldset>
		<div class="form-group" id="animation_file1">
			<label class="col-md-3 control-label">GIF/ZIP file</label>
			<div class="col-md-9">
				<input type="file" name="animation_file1" />
				<span class="help-block"></span>
			</div>
		</div>	
				
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">PT2/PT3 music</label>
			<div class="col-md-9">
				<input type="file" name="music_file" />
			</div>
		</div>	

		<div class="form-group form-group-margin" id="border">
			<label class="col-md-3 control-label">Border</label>
			<div class="col-md-2">
				<select name="border" class="form-control">
					<option value="0" style="background-color: #000000;">black</option>
					<option value="1" style="background-color: #0000cc;">blue</option>
					<option value="2" style="background-color: #cc0000;">red</option>
					<option value="3" style="background-color: #cc00cc;">magenta</option>
					<option value="4" style="background-color: #00cc00;">green</option>
					<option value="5" style="background-color: #00cccc;">cyan</option>
					<option value="6" style="background-color: #cccc00;">yellow</option>
					<option value="7" selected="selected" style="background-color: #cccccc;">white</option>
				</select>
			</div>
		</div>	
		
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">INK</label>
			<div class="col-md-2">
				<select name="ink" class="form-control">
					<option value="0" selected="selected" style="background-color: #000000;">black</option>
					<option value="1" style="background-color: #0000cc;">blue</option>
					<option value="2" style="background-color: #cc0000;">red</option>
					<option value="3" style="background-color: #cc00cc;">magenta</option>
					<option value="4" style="background-color: #00cc00;">green</option>
					<option value="5" style="background-color: #00cccc;">cyan</option>
					<option value="6" style="background-color: #cccc00;">yellow</option>
					<option value="7" style="background-color: #cccccc;">white</option>
				</select>
			</div>
			<label class="col-md-1 control-label">PAPER</label>
			<div class="col-md-2">
				<select name="paper" class="form-control">
					<option value="0" style="background-color: #000000;">black</option>
					<option value="1" style="background-color: #0000cc;">blue</option>
					<option value="2" style="background-color: #cc0000;">red</option>
					<option value="3" style="background-color: #cc00cc;">magenta</option>
					<option value="4" style="background-color: #00cc00;">green</option>
					<option value="5" style="background-color: #00cccc;">cyan</option>
					<option value="6" style="background-color: #cccc00;">yellow</option>
					<option value="7" selected="selected" style="background-color: #cccccc;">white</option>
				</select>
			</div>
			<div class="col-md-2">
				<label class="checkbox-inline">
					<input type="hidden" name="bright" value="0" />
					<input type="checkbox" name="bright" value="1" /> BRIGHT 
				</label>
			</div>
		</div>	
				
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Speed</label>
			<div class="col-md-3">
				<select name="speed1" class="form-control">
<?php foreach ($speeds as $s) {
	if (is_array($s)) {
		echo '<option value="'.$s['value'].'">'.$s['desc'].'</option>';
	}
	else {
		echo '<option value="'.$s.'">'.$s.'</option>';
	}
} ?>
				</select>
			</div>
		</div>	
		
		<br />
		
		<div class="form-group">
			<div class="col-md-offset-3 col-md-9">
				<button id="m-d-n-w" type="submit" class="btn btn-primary btn-lg">Please sit back and relax!</button>
			</div>
		</div>
	</fieldset>
</form>