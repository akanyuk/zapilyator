<?php
/**
'active_field' function

@var string $name           input field name
@var string $value          input field value
@var string $type           One of: str|text (DEFAULT), int, float, date, select, textarea, password, bool|checkbox
@var bool   $required       Field required
@var string $id             input field id
@var string $class          input field class
@var string $desc           field description (label)
@var string $width          input field width (i.e. '100px')
@var string $maxlength      Value maxlength (only for type="text" and type="password")
@var string $placeholder    Input field placeholder

@var int    $labelCols          Number of columns for label
@var int    $inputCols          Number of columns for input field
@var bool   $vertical           Disable `form-horizontal` support
@var bool   $disable_help_block Do not display help block
@var string $help_block_text    Default help block text (overrides by error messages

// Textarea related
@var int    $rows 		    Number rows
@var string $height 		Textarea height (i.e. '100px')

// Select related
@var array  $options        An array with selectbox options. One piece must be string or array with fields: 'id' and 'desc'

// datetimepicker related			
@var int $startDate 
@var int $endDate 
@var bool $withTime          Create input time fields
@var bool $editable          Enable manual edit of date
			
// int | float related			
@var float $min             Min value for type="number"
@var float $max             Max value for type="number"
@var float $decimals        Number of decimals for float value
		
@var string $rel 		    "rel" attribute (deprecated)
 
Usage:
 1. active_field(array('name'=>"name", 'value'=>$value, 'desc'=>"Имя", 'type'=>"text", 'required'=>true))
 2. active_field(array('attributes'=>$array, 'value'=>$value))
 3. active_field('set_defaults', array('varname' => $value))
 */
function active_field($params, $additional = null) {
	static $defaults = array();
	
	if ($params == 'set_defaults') {
		foreach ($additional as $varname=>$value) {
			$defaults[$varname] = $value;
		}
		
		return;
	}
	
	foreach(array('name','type','value','required','options','id','class','desc','rel','width','maxlength','placeholder','labelCols','inputCols','vertical','disable_help_block','help_block_text','rows','height','startDate','endDate','withTime','editable','min','max','decimals') as $varname) {
		if (isset($params[$varname])) {
			$$varname = $params[$varname];
		}
		elseif (isset($params['attributes'][$varname])) {
			$$varname = $params['attributes'][$varname];
		}
		elseif (isset($defaults[$varname]) && $defaults[$varname] !== false) {
			$$varname = $defaults[$varname];
		}
		else {
			$$varname = false;
		}
	}

	if (!$type) $type = 'text';
	if (!$options) $options = array();
	if ($rel) $rel = ' rel="'.$rel.'"';
	if ($required) $required = ' class="required"';
	if ($maxlength) $maxlength = ' maxlength="'.intval($maxlength).'"';
	if ($placeholder) $placeholder = ' placeholder="'.$placeholder.'"';
	if ($rows) $rows = ' rows="'.intval($rows).'"';

	// Numbers related
	if ($min !== false) $min = ' min="'.floatval($min).'"';
	if ($max) $max = ' max="'.floatval($max).'"';
	
	if ($type == 'int') {
		$step = '0';
	}
	elseif ($type == 'float') {
		$step = '0.'.sprintf('%0'.($decimals ? $decimals : 2).'d', 1);
	}
	
	$style = array();
	if ($width && $type != 'date') $style[] = 'width: '.$width;
	if ($height) $style[] = 'height: '.$height;
	$style = empty($style) ? '' : ' style="'.implode(' ',$style).'"';

	$labelCols = $labelCols ? intval($labelCols) : 3;
	$inputCols = $inputCols ? intval($inputCols) : ($type == 'date' ? 4 : 9);
	
	if ($labelCols + $inputCols > 8) {
		$helpblock_position = 'inside';
	}
	else {
		$helpblock_position = 'outside';
		$helpblock_outside_cols = 12 - $labelCols - $inputCols; 
	}
	
	$labelDesc = $type == 'checkbox' || $type == 'bool' ? '' : $desc;
	
	$input_id_name = $id ? ' id="'.$id.'"' : '';
	$input_id_name = $name ? $input_id_name.' name="'.$name.'"' : $input_id_name;
	
	if ($type == 'checkbox' || $type == 'bool') {
		ob_start();
?>
		<div data-active-container="<?php echo $id ? $id : $name?>" class="form-group form-group-checkbox clearfix">
			<?php echo $vertical ? '' : '<div class="col-md-offset-'.$labelCols.' col-md-'.$inputCols.'">'?>
				<div class="checkbox">
					<label>
						<input type="hidden" name="<?php echo $name?>" value="0" />
				   		<input <?php echo $input_id_name?> type="checkbox" value="1" <?php echo $value ? ' checked="checked"' : ''?> />
				   		<?php echo $desc?>
					</label>
				</div>
			<?php echo $vertical ? '' : '</div>'?>
		</div>
<?php 				
		return ob_get_clean();
	}
	
	ob_start();
?>
	<div data-active-container="<?php echo $id ? $id : $name?>" class="form-group clearfix">
		<?php if ($labelDesc):?>
		<label for="<?php echo $id ? $id : $name?>" class="<?php echo $vertical ? '' : 'col-md-'.$labelCols.' '?>control-label"><?php echo $required ? '<strong>'.$labelDesc.'</strong>' : $labelDesc?></label>
		<?php endif; ?>
		<?php echo $vertical ? '' : '<div class="col-md-'.$inputCols.'">'?>
<?php if ($type == 'date'): 

		$startDate = $startDate ? time() - intval($startDate * 86400) : time() - 86400 * 365 * 100;   
		$endDate = $endDate ? time() - intval($endDate * 86400) : time();

		NFW::i()->registerResource('jquery.activeForm/bootstrap-datetimepicker.min.js');
		NFW::i()->registerResource('jquery.activeForm/bootstrap-datetimepicker.min.css');
		NFW::i()->registerResource('jquery.activeForm/bootstrap-datetimepicker.ru.js');		
?>
	    <div data-language="<?php echo NFW::i()->user['language']?>" style="white-space: nowrap;">
	    	<input data-datepicker="1" data-editable="<?php echo $editable ? '1' : '0'?>" data-startDate="<?php echo date($withTime ? 'd.m.Y H:i' : 'd.m.Y', $startDate)?>" data-endDate="<?php echo date($withTime ? 'd.m.Y H:i' : 'd.m.Y', $endDate)?>" type="text" class="form-control" style="display: inline; width: 150px;" name="<?php echo $name?>" value="<?php echo $value ? date($withTime ? 'd.m.Y H:i' : 'd.m.Y', $value) : ''?>" data-unixTimestamp="<?php echo intval($value)?>" data-withTime="<?php echo $withTime?>"/><span id="set-date" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-calendar"></span></span><?php echo $required ? '' : '<span id="remove-date" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-remove"></span></span>'?>
	    </div>
<?php elseif ($type == 'select'): ?>
		<select <?php echo $input_id_name?> class="form-control<?php echo $class ? ' '.$class : ''?>"><?php foreach ($options as $o) { ?>
		<?php if (is_array($o)): ?>
			<option value="<?php echo $o['id']?>"<?php echo ($o['id'] == $value) ? ' selected="selected"' : ''?>><?php echo isset($o['title']) ? $o['title'] : $o['desc']?></option>
		<?php else: ?>
			<option value="<?php echo $o?>"<?php echo ($o == $value) ? ' selected="selected"' : ''?>><?php echo $o?></option>
		<?php endif; ?>
		<?php } ?>
		</select>
<?php elseif ($type == 'textarea'): ?>
		<textarea <?php echo $input_id_name?> class="form-control <?php echo $class?>"<?php echo $placeholder.$rows.$style?>><?php echo htmlspecialchars($value)?></textarea>
<?php elseif ($type == 'password'): ?>
		<input type="password" <?php echo $input_id_name?> class="form-control <?php echo $class?>" <?php echo $placeholder.$maxlength?> value="<?php echo htmlspecialchars($value)?>" />
<?php elseif ($type == 'int' || $type == 'float'): ?>
		<input type="number" <?php echo $input_id_name.$min.$max.$style?> step="<?php echo $step?>" class="form-control <?php echo $class?>"<?php echo $placeholder?> value="<?php echo htmlspecialchars($value)?>" />
<?php else: ?>
		<input type="text" <?php echo $input_id_name?> class="form-control <?php echo $class?>"<?php echo $placeholder.$maxlength.$style?> value="<?php echo htmlspecialchars($value)?>" />
<?php endif; ?>
		<?php if ($disable_help_block): echo $vertical ? '' : '</div>'; else: ?>
		<?php echo $vertical ? '<span class="help-block">'.$help_block_text.'</span>' : ($helpblock_position == 'outside' ? '</div><div class="col-md-'.$helpblock_outside_cols.'"><span class="help-block">'.$help_block_text.'</span></div>' : '<span class="help-block">'.$help_block_text.'</span></div>')?>
		<?php endif; ?>
	</div>
<?php 
	return ob_get_clean();
}