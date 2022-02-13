<?php
	NFW::i()->registerResource('jquery.activeForm');
?>
<script type="text/javascript">
$(document).ready(function() {
	var f = $('form[id="demo-maker"]');
	f.activeForm({
		'success': function(response){
			$('div[id="result-dialog"]').find('div[id="result-log"]').html(response.log);
			$('div[id="result-dialog"]').find('a[id="download-result"]').attr('href', response.download);
			$('div[id="result-dialog"]').modal('show');
		}
	});
});
</script>

<style>
	form#demo-maker .form-group-margin { margin-bottom: 10px; }
	
	div#result-log { font-family: monospace; font-size: 85%; white-space: pre-wrap; }
	div#result-log .error { font-weight: bold; color: red; }
</style>

<div id="result-dialog" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">Result</h4>
			</div>
			<div id="content" class="modal-body"><div id="result-log"></div></div>
			<div class="modal-footer">
				<a id="download-result" class="btn btn-primary">Download</a>
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<h1><span class="pull-right label label-danger">DISCONTINUED!</span>Old Good Zapilyator</h1>
<br />
<form id="demo-maker" class="form-horizontal" action="?action=make" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="262144" />
	<fieldset>
		<legend>Main setup</legend>
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">PT2/PT3 music</label>
			<div class="col-md-9">
				<input type="file" name="music_file" />
			</div>
		</div>	
		
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Splash file</label>
			<div class="col-md-9">
				<input type="file" name="splash_file" />
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
					<option value="7" style="background-color: #cccccc;">white</option>
				</select>
			</div>
		</div>	
		
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Splash delay</label>
			<div class="col-md-2">
				<select name="splash_delay" class="form-control">
					<option value="1">1 pattern</option>
					<option value="2">2 patterns</option>
					<option value="3">3 patterns</option>
					<option value="4">4 patterns</option>
					<option value="5">5 patterns</option>
				</select>
			</div>
		</div>		
	
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Analyzator / Sense</label>
			<div class="col-md-3">
				<select name="analyzator_splash_chanel" class="form-control">
					<option value="0">disabled</option>
					<option value="8">A</option>
					<option value="9">B</option>
					<option value="10">C</option>
				</select>
			</div>
			<div class="col-md-3">
				<select name="analyzator_splash_sens" class="form-control">
					<option value="8">08</option>
					<option value="9">09</option>
					<option value="10">10</option>
					<option value="11">11</option>
					<option value="12">12</option>
					<option value="13">13</option>
					<option value="14">14</option>
					<option value="15">15</option>
				</select>
			</div>
			<div class="col-md-3">Use FLASH-color in Splash image for analyzator area.</div>
		</div>		
	</fieldset>
	
	<fieldset>
		<legend>Background setup</legend>
		
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Background</label>
			<div class="col-md-9">
				<input type="file" name="gif_background" />
			</div>
		</div>	

		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Animation Border</label>
			<div class="col-md-2">
				<select name="animation_border" class="form-control">
					<option value="0" style="background-color: #000000;">black</option>
					<option value="1" style="background-color: #0000cc;">blue</option>
					<option value="2" style="background-color: #cc0000;">red</option>
					<option value="3" style="background-color: #cc00cc;">magenta</option>
					<option value="4" style="background-color: #00cc00;">green</option>
					<option value="5" style="background-color: #00cccc;">cyan</option>
					<option value="6" style="background-color: #cccc00;">yellow</option>
					<option value="7" style="background-color: #cccccc;">white</option>
				</select>
			</div>
		</div>	
		
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">INK</label>
			<div class="col-md-2">
				<select name="gif_ink" class="form-control">
					<option value="0" style="background-color: #000000;">black</option>
					<option value="1" style="background-color: #0000cc;">blue</option>
					<option value="2" style="background-color: #cc0000;">red</option>
					<option value="3" style="background-color: #cc00cc;">magenta</option>
					<option value="4" style="background-color: #00cc00;">green</option>
					<option value="5" style="background-color: #00cccc;">cyan</option>
					<option value="6" style="background-color: #cccc00;">yellow</option>
					<option value="7" style="background-color: #cccccc;" selected="selected">white</option>
				</select>
			</div>
			<label class="col-md-1 control-label">PAPER</label>
			<div class="col-md-2">
				<select name="gif_paper" class="form-control">
					<option value="0" style="background-color: #000000;">black</option>
					<option value="1" style="background-color: #0000cc;">blue</option>
					<option value="2" style="background-color: #cc0000;">red</option>
					<option value="3" style="background-color: #cc00cc;">magenta</option>
					<option value="4" style="background-color: #00cc00;">green</option>
					<option value="5" style="background-color: #00cccc;">cyan</option>
					<option value="6" style="background-color: #cccc00;">yellow</option>
					<option value="7" style="background-color: #cccccc;">white</option>
				</select>
			</div>
			<div class="col-md-2">
				<label class="checkbox-inline">
					<input type="hidden" name="gif_bright" value="0" />
					<input type="checkbox" name="gif_bright" value="1" checked="checked" /> BRIGHT 
				</label>
			</div>
		</div>	
				
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Analyzator / Sense</label>
			<div class="col-md-3">
				<select name="analyzator_chanel" class="form-control">
					<option value="0">disabled</option>
					<option value="8">A</option>
					<option value="9">B</option>
					<option value="10">C</option>
				</select>
			</div>
			<div class="col-md-3">
				<select name="analyzator_sens" class="form-control">
					<option value="8">08</option>
					<option value="9">09</option>
					<option value="10">10</option>
					<option value="11">11</option>
					<option value="12">12</option>
					<option value="13">13</option>
					<option value="14">14</option>
					<option value="15">15</option>
				</select>
			</div>
			<div class="col-md-3">Use FLASH-color in Background image for analyzator area.</div>
		</div>		
	</fieldset>
	
	<fieldset>
		<legend>Animation 1</legend>
		
		<div class="form-group" id="gif_file1">
			<label class="col-md-3 control-label">GIF file</label>
			<div class="col-md-5">
				<input type="file" name="gif_file1" />
				<span class="help-block"></span>
			</div>
			<div class="col-md-4"><div class="alert alert-info" style="margin-bottom: 0;">Maximum safe output: ~40Kb</div></div>
		</div>	
				
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Speed</label>
			<div class="col-md-3">
				<select name="speed1" class="form-control">
					<option value="0">Take from GIF</option>
					<option value="1">1 - fast</option>
					<option value="2">2</option>
					<option value="3">3</option>
					<option value="4">4</option>
					<option value="5">5</option>
					<option value="6">6</option>
					<option value="7">7</option>
					<option value="8">8 - slow</option>
				</select>
			</div>
		</div>	
	</fieldset>
	
	<fieldset>
		<legend>Animation 2</legend>
		
		<div class="form-group" id="gif_file2">
			<label class="col-md-3 control-label">GIF file</label>
			<div class="col-md-5">
				<input type="file" name="gif_file2" />
				<span class="help-block"></span>
			</div>
			<div class="col-md-4"><div class="alert alert-info" style="margin-bottom: 0;">Maximum safe output: ~16Kb</div></div>
		</div>	
				
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Speed</label>
			<div class="col-md-3">
				<select name="speed2" class="form-control">
					<option value="0">Take from GIF</option>
					<option value="1">1 - fast</option>
					<option value="2">2</option>
					<option value="3">3</option>
					<option value="4">4</option>
					<option value="5">5</option>
					<option value="6">6</option>
					<option value="7">7</option>
					<option value="8">8 - slow</option>
				</select>
			</div>
		</div>	
	</fieldset>
	
	<fieldset>
		<legend>Animation 3</legend>
		
		<div class="form-group" id="gif_file3">
			<label class="col-md-3 control-label">GIF file</label>
			<div class="col-md-5">
				<input type="file" name="gif_file3" />
				<span class="help-block"></span>
			</div>
			<div class="col-md-4"><div class="alert alert-info" style="margin-bottom: 0;">Maximum safe output: ~16Kb</div></div>
		</div>	
				
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Speed</label>
			<div class="col-md-3">
				<select name="speed3" class="form-control">
					<option value="0">Take from GIF</option>
					<option value="1">1 - fast</option>
					<option value="2">2</option>
					<option value="3">3</option>
					<option value="4">4</option>
					<option value="5">5</option>
					<option value="6">6</option>
					<option value="7">7</option>
					<option value="8">8 - slow</option>
				</select>
			</div>
		</div>	
	</fieldset>
		
	<fieldset>
		<legend>Scroll setup</legend>
	
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Text</label>
			<div class="col-md-9">
				<textarea name="scroll_text" class="form-control" rows="7"></textarea>
			</div>
		</div>	

		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Scroll font</label>
			<div class="col-md-9">
				<label class="radio-inline">
					<input type="radio" checked name="scroll_font" value="1"/><img src="<?php echo NFW::i()->base_path?>resources/demo_maker/font1.png" />
				</label>
				<label class="radio-inline">
					<input type="radio" name="scroll_font" value="2"/><img src="<?php echo NFW::i()->base_path?>resources/demo_maker/font2.png" />
				</label>
				<label class="radio-inline">
					<input type="radio" name="scroll_font" value="3"/><img src="<?php echo NFW::i()->base_path?>resources/demo_maker/font3.png" />
				</label>
			</div>
		</div>
		
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">INK</label>
			<div class="col-md-2">
				<select name="scroll_ink" class="form-control">
					<option value="0" style="background-color: #000000;">black</option>
					<option value="1" style="background-color: #0000cc;">blue</option>
					<option value="2" style="background-color: #cc0000;">red</option>
					<option value="3" style="background-color: #cc00cc;">magenta</option>
					<option value="4" style="background-color: #00cc00;">green</option>
					<option value="5" style="background-color: #00cccc;">cyan</option>
					<option value="6" style="background-color: #cccc00;" selected="selected">yellow</option>
					<option value="7" style="background-color: #cccccc;">white</option>
				</select>
			</div>
			<label class="col-md-1 control-label">PAPER</label>
			<div class="col-md-2">
				<select name="scroll_paper" class="form-control">
					<option value="0" style="background-color: #000000;">black</option>
					<option value="1" style="background-color: #0000cc;">blue</option>
					<option value="2" style="background-color: #cc0000;">red</option>
					<option value="3" style="background-color: #cc00cc;">magenta</option>
					<option value="4" style="background-color: #00cc00;">green</option>
					<option value="5" style="background-color: #00cccc;">cyan</option>
					<option value="6" style="background-color: #cccc00;">yellow</option>
					<option value="7" style="background-color: #cccccc;">white</option>
				</select>
			</div>
			<div class="col-md-2">
				<label class="checkbox-inline">
					<input type="hidden" name="scroll_bright" value="0" />
					<input type="checkbox" name="scroll_bright" value="1" checked="checked" /> BRIGHT 
				</label>
			</div>
		</div>	
				
		<div class="form-group form-group-margin">
			<label class="col-md-3 control-label">Position</label>
			<div class="col-md-3">
				<select name="scroll_position" class="form-control">
					<option value="#401f|#5800">01</option>
					<option value="#403f|#5820">02</option>
					<option value="#405f|#5840">03</option>
					<option value="#407f|#5860">04</option>
					<option value="#409f|#5880">05</option>
					<option value="#40bf|#58a0">06</option>
					<option value="#40de|#58c0">07</option>
					<option value="#40ff|#58e0">08</option>
					
					<option value="#481f|#5900">09</option>
					<option value="#483f|#5920">10</option>
					<option value="#485f|#5940">11</option>
					<option value="#487f|#5960">12</option>
					<option value="#489f|#5980">13</option>
					<option value="#48bf|#59a0">14</option>
					<option value="#48df|#59c0">15</option>
					<option value="#48ff|#59e0">16</option>
					
					<option value="#501f|#5a00">17</option>
					<option value="#503f|#5a20">18</option>
					<option value="#505f|#5a40">19</option>
					<option value="#507f|#5a60">20</option>
					<option value="#509f|#5a80">21</option>
					<option value="#50bf|#5aa0">22</option>
					<option value="#50df|#5ac0">23</option>
				</select>
			</div>
		</div>	
		
		<br />
		
		<div class="form-group">
			<div class="col-md-offset-3 col-md-9">
				<button type="submit" class="btn btn-primary btn-lg">MAKE DEMO NOT WAR!</button>
			</div>
		</div>
	</fieldset>
</form>