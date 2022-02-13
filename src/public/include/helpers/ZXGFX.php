<?php
/**
 * ZX Spectrum SCREEN converter to PNG or animated GIF file
 * 
 * Some code parts taken from:
 * - theX.pvd decoder (http://thex.untergrund.net/pvd/)
 * - moroz1999 sources (http://www.zx.pk.ru/member.php?u=50)
 *
 * @author Andrey nyuk Marinov (aka.nyuk@gmail.com)
 * @link http://nyuk.retropc.ru/gfx_converter
 * @version 0.51 (2010-03-14) 
 */
class ZXGFX {
	private $palettes = array(
		'pulsar' 	=> 	'00,76,CD,E9,FF,9F:FF,00,00;00,FF,00;00,00,FF',
		'alone' 	=>	'00,60,A0,E0,FF,A0:100,00,00;00,100,00;00,00,100',
//		'alone' 	=>	'00,60,A0,E0,FF,A0:FF,00,00;00,FF,00;00,00,FF',
		'orthodox' 	=> 	'00,76,CD,E9,FF,9F:D0,00,00;00,E4,00;00,00,FF',
		'Mars'		=> 	'00,80,C0,E0,FF,C8:FF,00,00;40,C0,00;00,40,C0',
		'Ocean'		=>	'20,80,A0,C0,E0,A8:D0,00,30;00,D0,30;00,00,FF',
		'Grey'		=>	'00,80,C0,E0,FF,C8:49,92,24;49,92,24;49,92,24'	
	);
	
	private $borders = array(
		'none' => array(
			'0.5' => array('width' => 128, 'height' => 96),
			'1' => array('width' => 256, 'height' => 192),
			'2' => array('width' => 512, 'height' => 384),
			'3' => array('width' => 768, 'height' => 576),
		),
		'small' => array(
			'0.5' => array('width' => 160, 'height' => 120),
			'1' => array('width' => 320, 'height' => 240),
			'2' => array('width' => 640, 'height' => 480),
			'3' => array('width' => 960, 'height' => 720),
			
		),
		'complete' => array(
			'0.5' => array('width' => 192, 'height' => 150),
			'1' => array('width' => 384, 'height' => 300),
			'2' => array('width' => 768, 'height' => 600),
			'3' => array('width' => 1152, 'height' => 900),
		),
	);
	
	private $hidden_colors = array(
		1 => array('R' => 0xFF, 'G' => 0xA5, 'B' => 0x00),
		2 => array('R' => 0x00, 'G' => 0xA5, 'B' => 0xFF),
		3 => array('R' => 0x00, 'G' => 0xFF, 'B' => 0xA5),
		4 => array('R' => 0x00, 'G' => 0xEE, 'B' => 0x69),
		5 => array('R' => 0x3C, 'G' => 0x83, 'B' => 0x71),
	);

	// Border size
	private $border = 'none';
	
	private $border_color = 0;
	
	// Generated from palette colors
	private $colors = false;
	
	// Generated from palette gigascreen colors
	private $gigaColors = false;

	// Generated from palette colors for 8-colors mode
	private $colors8 = false;
	
	private $mask = array(0x80, 0x40, 0x20, 0x10, 0x08, 0x04, 0x02, 0x01);
	
	// Original SCR/IMG/MGS..
	private $src;
	
	private $input_type = 'screen';		// Image input format
	private $MGS_CharSize = 1;			// MGS screen format
	private $is_flash_img	= false;	// Is loaded image with FLASH attribute
	private $output_type = 'png';
	private $output_scale = 1;

	// FLASH attribute -> GIF animation related
	private $animation_delay = 50;
	
	private $options = array(
		'forceOneFrame' 	=> false,		// Force remove secon frame in GIF-images
		'showHiddenPixels' 	=> false,		// Show hidden pixels (INK=PAPER) 
		'hiddenColor' 		=> 0xFFA500,	// Color for 'showHiddenPixels' option 
		'isTransparent'		=> true,		// Show hidden pixels (INK=PAPER) as transparent
	);

	private $filters = array(
		'light-blur'		=> false,		// Blur image
		'gaussian-blur'		=> false,		// Gaussian blur image
		'interleave'		=> false,		// Interleave image
		'interleaveLevel' 	=> 0,			
	);
	
	private function generateColors($palette, $S = 0x100) {
		for ($i=0; $i<16; $i++) {
			$one = ($i & 0x08) ? $palette['BB'] : $palette['NN'];

			$r = ($i & 0x02) ? $one : $palette['ZZ'];
			$g = ($i & 0x04) ? $one : $palette['ZZ'];
			$b = ($i & 0x01) ? $one : $palette['ZZ'];
			
			$redChannel = round(($r*$palette['R11'] + $g*$palette['R12'] + $b*$palette['R13'])/$S);
			$greenChannel = intval(($r*$palette['R21'] + $g*$palette['R22'] + $b*$palette['R23'])/$S);
			$blueChannel = intval(($r*$palette['R31'] + $g*$palette['R32'] + $b*$palette['R33'])/$S);

			$this->colors[$i] = $redChannel*0x010000 + $greenChannel*0x0100 + $blueChannel;
		}
	}

	private function generateColors8($palette, $S = 0x100) {
		for ($i=0; $i<8; $i++) {
			$r = ($i & 0x02) ? $palette['NN'] : $palette['ZZ'];
			$g = ($i & 0x04) ? $palette['NN'] : $palette['ZZ'];
			$b = ($i & 0x01) ? $palette['NN'] : $palette['ZZ'];
			
			$redChannel = round(($r*$palette['R11'] + $g*$palette['R12'] + $b*$palette['R13'])/$S);
			$greenChannel = intval(($r*$palette['R21'] + $g*$palette['R22'] + $b*$palette['R23'])/$S);
			$blueChannel = intval(($r*$palette['R31'] + $g*$palette['R32'] + $b*$palette['R33'])/$S);

			$this->colors8[$i] = $redChannel*0x010000 + $greenChannel*0x0100 + $blueChannel;
		}
	}
		
	private function generateGigaColors($palette, $S = 0x100) {
		$cache = array(0 => 'Z', 1 => 'N', 2 => 'Z', 3 => 'B');

		$palette['BN'] = $palette['NB'];
		$palette['BZ'] = $palette['ZB'];
		$palette['NZ'] = $palette['ZN'];
		
		for ($i1=0; $i1<16; $i1++) for ($i2=0; $i2<16; $i2++) {
			$brightness1 = ($i1 & 0x08) ? 1 : 0;
			$brightness2 = ($i2 & 0x08) ? 1 : 0;			
			
			$r = $palette[$cache[($brightness1 * 2) + (($i1 & 0x02) ? 1 : 0)].$cache[($brightness2 * 2) + (($i2 & 0x02) ? 1 : 0)]];
			$g = $palette[$cache[($brightness1 * 2) + (($i1 & 0x04) ? 1 : 0)].$cache[($brightness2 * 2) + (($i2 & 0x04) ? 1 : 0)]];
			$b = $palette[$cache[($brightness1 * 2) + (($i1 & 0x01) ? 1 : 0)].$cache[($brightness2 * 2) + (($i2 & 0x01) ? 1 : 0)]];
			
			$redChannel = round(($r*$palette['R11'] + $g*$palette['R12'] + $b*$palette['R13'])/$S);
			$greenChannel = round(($r*$palette['R21'] + $g*$palette['R22'] + $b*$palette['R23'])/$S);
			$blueChannel = round(($r*$palette['R31'] + $g*$palette['R32'] + $b*$palette['R33'])/$S);

			$this->gigaColors[$i1][$i2] = $redChannel*0x010000 + $greenChannel*0x0100 + $blueChannel;
		}
	}
	
	private function detectInputType() {
		if (strlen($this->src) == 18432) {
			$this->input_type = '3color';			
		}
		elseif (strlen($this->src) == 36871 && substr($this->src, 0, 3) == 'MGS') {
			$this->input_type = 'mgs';			
			$this->MGS_CharSize	= ord($this->src[4]);
			if (!in_array($this->MGS_CharSize, array(1,2,4,8))) return false;
		}
		elseif (strlen($this->src) == 19456 && substr($this->src, 0, 3) == 'MGH') {
			$this->input_type = 'mg1';
			$this->MGS_CharSize	= 1;
		}
		elseif (strlen($this->src) == 18688 && substr($this->src, 0, 3) == 'MGH') {
			$this->input_type = 'mg2';
			$this->MGS_CharSize	= 2;
		}
		elseif (strlen($this->src) == 15616 && substr($this->src, 0, 3) == 'MGH') {
			$this->input_type = 'mg4';
			$this->MGS_CharSize	= 4;
		}
		elseif (strlen($this->src) == 14080 && substr($this->src, 0, 3) == 'MGH') {
			$this->input_type = 'mg8';
			$this->MGS_CharSize	= 8;
		}
		elseif (strlen($this->src) == 13824) {
			$this->input_type = 'gigascreen';			
		}
		elseif (strlen($this->src) == 6912) { 
			$this->input_type = 'screen';
		}
		elseif (strlen($this->src) == 6144) {
			$this->input_type = 'bw-screen';
			$this->src.=str_repeat(chr(7), 768);
		}
		else
			return false;
		
		return $this->input_type;
	}
	
	private function generateImage() {
		// Save values
		$save_colors = $this->colors;
		$save_border = $this->border_color;

		if (!$this->colors) $this->setPalette();
		
		$options = array('secondFrame' => false);
		$frame2 = false;

		if ($this->input_type == '3color') {
			$frame = $this->create3ColorFrame($options);
		}
		elseif ($this->input_type == 'mgs') {
			$frame = $this->createMgsFrame($options);
		}
		elseif ($this->input_type == 'mg8' || $this->input_type == 'mg4' || $this->input_type == 'mg2' || $this->input_type == 'mg1') {
			$this->convertMgX();
			$frame = $this->createMgsFrame($options);
		}
		// Gigascreen
		elseif ($this->input_type == 'gigascreen') {
			$frame = $this->createGigascreenFrame($options);
		}
		// Normal screen or screen without attributes
		else {
			$frame = $this->createScreenFrame($options);
			if ($this->output_type == 'gif' && $this->is_flash_img) {
				$options['secondFrame'] = true;
				$frame2 = $this->createScreenFrame($options);
			}
		}

	    if ($this->output_type == 'gif') {
			// First frame
			ob_start();
            imagegif ($frame);
            $gif_frames[] = ob_get_contents();
            ob_end_clean();
            $this->destroyFrame($frame);

            if ($frame2 && !$this->options['forceOneFrame']) {
				// Second frame
				ob_start();
	            imagegif ($frame2);
	            $gif_frames[] = ob_get_contents();
	            ob_end_clean();
				if ($frame2) $this->destroyFrame($frame2);
					       
				$cur_e = error_reporting(0);
		        $GIF = new GIFEncoder($gif_frames, array($this->animation_delay, $this->animation_delay), 0, 2, -1, -1, -1, "bin");
		        $result_img = $GIF->GetAnimation();
				error_reporting($cur_e);
            }
            else {
            	$result_img = $gif_frames[0];
            }
        }
        // 'png' by default
		else {
	    	ob_start();
			imagepng($frame);
			$result_img = ob_get_contents();
        	ob_end_clean();
        	$this->destroyFrame($frame);
	    }		
		
	    // Restore values
	    $this->colors = $save_colors;
	    $this->border_color = $save_border;
	    
        return $result_img;		
	}
	
	private function destroyFrame($im) {
		imagedestroy($im);
	}

	private function createScreenFrame($options = array()) {
		// By default we process only one frame
		$this->is_flash_img = false;
		
		$im = @imagecreatetruecolor(256, 192) or die("Cannot Initialize new GD image stream");
		imagefill($im, 0, 0, 0);
		
		for($y = 0; $y < 192; ++$y) {
			$pixelLine = 32 * (($y & 0xc0) | (($y << 3) & 0x38) | (($y >> 3) & 0x07));
	      	$attr      = 6144 + (($y & 0xf8) << 2);
	
	      	for($x = 0; $x < 32; ++$x) {
	        	$chr_pixels   = ord($this->src[$pixelLine + $x]);
	        	$chr_attr     = ord($this->src[$attr + $x]);
	        
	        	if ($this->input_type == 'bw-screen') {
		        	$paper = 7;
		        	$ink   = 0;
	        	}
	        	else {
		        	$paper = ($chr_attr >> 3) & 0x0f;
		        	$ink   = ($chr_attr & 0x07) | ($paper & 0x08);
	        	}
	        	
	        	if (($chr_attr & 0x80) && $paper != $ink) $this->is_flash_img = true;
					
				for ($bit = 0; $bit < 8; ++$bit) {
					if ($this->options['showHiddenPixels'] && $ink == $paper && ($chr_pixels & $this->mask[$bit])) {
						$color = $this->options['hiddenColor'];
					}
					// Flashing 		          
		          	elseif ($options['secondFrame'] && ($chr_attr & 0x80)) {
		            	$color = ($chr_pixels & $this->mask[$bit]) ? $this->colors[$paper] : $this->colors[$ink];
		          	}
		          	else {
		          		$color = ($chr_pixels & $this->mask[$bit]) ? $this->colors[$ink] : $this->colors[$paper];
		          	}

		        	if ($color) {
						imagesetpixel($im, $x*8 + $bit, $y, $color);
		        	}
	        	}
	    	}
		}
	    
	    return $this->finalizeFrame($im);;
	}
	
	
	private function createGigascreenFrame($options = array()) {
		$im = @imagecreatetruecolor(256, 192) or die("Cannot Initialize new GD image stream");
		imagefill($im, 0, 0, 0);
		
		for($y = 0; $y < 192; ++$y) {
			$pixelLine = 32 * (($y & 0xc0) | (($y << 3) & 0x38) | (($y >> 3) & 0x07));
	      	$attr      = 6144 + (($y & 0xf8) << 2);
	
	      	for($x = 0; $x < 32; ++$x) {
	        	$chr_pixels1   = ord($this->src[$pixelLine + $x]);
	        	$paper1 = (ord($this->src[$attr + $x]) >> 3) & 0x0f;
	        	$ink1   = (ord($this->src[$attr + $x]) & 0x07) | ($paper1 & 0x08);

	        	$chr_pixels2   = ord($this->src[$pixelLine + $x + 6912]);
	        	$paper2 = (ord($this->src[$attr + $x + 6912]) >> 3) & 0x0f;
	        	$ink2   = (ord($this->src[$attr + $x + 6912]) & 0x07) | ($paper2 & 0x08);
	        	
				for($bit = 0; $bit < 8; ++$bit) {
		        	$ci1 = ($chr_pixels1 & $this->mask[$bit]) ? $ink1 : $paper1;
		        	$ci2 = ($chr_pixels2 & $this->mask[$bit]) ? $ink2 : $paper2;
		        	
		        	$trans1 = ($this->options['showHiddenPixels'] && $ink1 == $paper1 && ($chr_pixels1 & $this->mask[$bit])) ? 1 : 0;
		        	$trans2 = ($this->options['showHiddenPixels'] && $ink2 == $paper2 && ($chr_pixels1 & $this->mask[$bit])) ? 1 : 0;
		        	
					if ($trans1 || $trans2) {
						imagesetpixel($im, $x*8 + $bit, $y, $this->options['hiddenColor']);
					}
					elseif ($this->gigaColors[$ci1][$ci2]) {
						imagesetpixel($im, $x*8 + $bit, $y, $this->gigaColors[$ci1][$ci2]);
		        	}
	        	}
	    	}
		}
	    
	    return $this->finalizeFrame($im);
	}

	private function createMgsFrame($options = array()) {
		$im = @imagecreatetruecolor(256, 192) or die("Cannot Initialize new GD image stream");
		imagefill($im, 0, 0, 0);
		
		// mgs colors => $this->gigaScreen
		$colors = array(0 => 0,	1 => 1,	2 => 9,	3 => 2,	4 => 10, 5 => 3, 6 => 11, 7 => 4, 8 => 12, 9 => 5, 10 => 13, 11 => 6, 12 => 14,	13 => 7, 14 => 15);
		
		// Set border color
		$b1 = ord($this->src[5]);
		$b2 = ord($this->src[6]);
		if (isset($this->gigaColors[$b1][$b2])) {
			$this->border_color = $this->gigaColors[$b1][$b2];
		} 
		
		for($y = 0; $y < 192; ++$y) {
			$pixelLine 	= $y * 32 + 7;
			
	      	for($x = 0; $x < 32; ++$x) {
	        	$chr_pixels1   = ord($this->src[$pixelLine + $x]);
	        	$chr_pixels2   = ord($this->src[$pixelLine + $x + 6144]);
	        	
	        	switch ($this->MGS_CharSize) {
	        		case 1:
		        		if ($x < 8 || $x > 23) {
				        	$ink1   = $colors[ord($this->src[12295 + ($y >> 3)*512 + $x*2])];
				        	$paper1 = $colors[ord($this->src[12296 + ($y >> 3)*512 + $x*2])];
				        	$ink2   = $colors[ord($this->src[24583 + ($y >> 3)*512 + $x*2])];
				        	$paper2 = $colors[ord($this->src[24584 + ($y >> 3)*512 + $x*2])];
		        		}
			        	else { 
				        	$ink1   = $colors[ord($this->src[12295 + $y*64 + $x*2])];
				        	$paper1 = $colors[ord($this->src[12296 + $y*64 + $x*2])];
				        	$ink2   = $colors[ord($this->src[24583 + $y*64 + $x*2])];
				        	$paper2 = $colors[ord($this->src[24584 + $y*64 + $x*2])];
			        	}
			        	break;
	        		case 2:
			        	$ink1   = $colors[ord($this->src[12295 + ($y >> 1)*128 + $x*2])];
			        	$paper1 = $colors[ord($this->src[12296 + ($y >> 1)*128 + $x*2])];
			        	$ink2   = $colors[ord($this->src[24583 + ($y >> 1)*128 + $x*2])];
			        	$paper2 = $colors[ord($this->src[24584 + ($y >> 1)*128 + $x*2])];
			        	break;
	        		case 4:
			        	$ink1   = $colors[ord($this->src[12295 + ($y >> 2)*256 + $x*2])];
			        	$paper1 = $colors[ord($this->src[12296 + ($y >> 2)*256 + $x*2])];
			        	$ink2   = $colors[ord($this->src[24583 + ($y >> 2)*256 + $x*2])];
			        	$paper2 = $colors[ord($this->src[24584 + ($y >> 2)*256 + $x*2])];
			        	break;
	        		case 8:
			        	$ink1   = $colors[ord($this->src[12295 + ($y >> 3)*512 + $x*2])];
			        	$paper1 = $colors[ord($this->src[12296 + ($y >> 3)*512 + $x*2])];
			        	$ink2   = $colors[ord($this->src[24583 + ($y >> 3)*512 + $x*2])];
			        	$paper2 = $colors[ord($this->src[24584 + ($y >> 3)*512 + $x*2])];
			        	break;
	        	}
	        	
	        	
	        	for($bit = 0; $bit < 8; ++$bit) {
		        	$ci1 = ($chr_pixels1 & $this->mask[$bit]) ? $ink1 : $paper1;
		        	$ci2 = ($chr_pixels2 & $this->mask[$bit]) ? $ink2 : $paper2;
		        	
		        	if ($this->options['showHiddenPixels'] && ($ink1 == $paper1 && ($chr_pixels1 & $this->mask[$bit]) || ($ink2 == $paper2 && ($chr_pixels1 & $this->mask[$bit])))) {
		        		$color = $this->options['hiddenColor'];
		        	}
		        	elseif($this->gigaColors[$ci1][$ci2]) {
		        		$color = $this->gigaColors[$ci1][$ci2];
		        	}
		        	else
		        		$color = false;
		        		
		        	if ($color)	imagesetpixel($im, $x*8 + $bit, $y, $color);
	        	}
	    	}
		}
	    
	    return $this->finalizeFrame($im);
	}

	private function create3ColorFrame($options = array()) {
		$im = @imagecreatetruecolor(256, 192) or die("Cannot Initialize new GD image stream");
		imagefill($im, 0, 0, 0);
		
		for($y = 0; $y < 192; ++$y) {
			$line = 32 * (($y & 0xc0) | (($y << 3) & 0x38) | (($y >> 3) & 0x07));
	
	      	for($x = 0; $x < 32; ++$x) {
	        	$chr1   = ord($this->src[$line + $x]);
	        	$chr2   = ord($this->src[$line + 6144 + $x]);
	        	$chr3   = ord($this->src[$line + 6144*2 + $x]);
	        	
				for ($bit = 0; $bit < 8; ++$bit) {
	          		$red 	= ($chr1 & $this->mask[$bit]) ? 2 : 0;
	          		$green 	= ($chr2 & $this->mask[$bit]) ? 4 : 0;
	          		$blue 	= ($chr3 & $this->mask[$bit]) ? 1 : 0;

	          		$color = $this->colors8[$red + $green + $blue];
	          		
		        	if ($color) {
						imagesetpixel($im, $x*8 + $bit, $y, $color);
		        	}
	        	}
	    	}
		}
	    
	    return $this->finalizeFrame($im);;
	}
		
	/**
	 * Convert loaded mg1..mg8 image to MGS format
	 * 
	 */
	private function convertMgX() {
		// $this->gigaScreen => mgs colors
		$colors = array(0 => 0,	1 => 1,	9 => 2,	2 => 3,	10 => 4, 3 => 5, 11 => 6, 4 => 7, 12 => 8, 5 => 9, 13 => 10, 6 => 11, 14 => 12, 7 => 13, 15 => 14, 8 => 0);
		
		$new_src = str_repeat(chr(0), 36871);
		
		// Move header
		$new_src[3] = $this->src[3]; 
		$new_src[4] = $this->src[4];
		$new_src[5] = $this->src[5];
		$new_src[6] = $this->src[6];
		
		// Move pixels & attributes
		for($y = 0; $y < 192; ++$y) {
			$pixelLine = 32 * (($y & 0xc0) | (($y << 3) & 0x38) | (($y >> 3) & 0x07)) + 256;
			
			switch ($this->MGS_CharSize) {
				case 8:
					$attr1 = 256 + 12288 + (($y & 0xf8) << 2);
					$attr2 = 256 + 12288 + 768 + (($y & 0xf8) << 2);
					break;
				case 4:
					$attr1 = 256 + 12288 + (($y & 0xfc) << 3);
					$attr2 = 256 + 12288 + 1536 + (($y & 0xfc) << 3);
					break;
				case 2:
					$attr1 = 256 + 12288 + (($y & 0xfe) << 4);
					$attr2 = 256 + 12288 + 3072 + (($y & 0xfe) << 4);
					break;
			}
			
	      	for($x = 0; $x < 32; ++$x) {
	      		// Convert bitplanes
	      		$new_src[$y*32 + $x + 7] = $this->src[$pixelLine + $x];
	      		$new_src[$y*32 + $x + 6151] = $this->src[$pixelLine + 6144 + $x];

	      		// Convert attributes
				if ($this->MGS_CharSize == 1) {
					if ($x < 8 || $x > 23) {
						$attr1 = 256 + 18432 + (($y & 0xf8) << 1);
						$attr2 = 256 + 18432 + 384 + (($y & 0xf8) << 1);
		        		$paper1 = (ord($this->src[$attr1 + ($x & 0x0f)]) >> 3) & 0x0f;
		        		$ink1   = (ord($this->src[$attr1 + ($x & 0x0f)]) & 0x07) | ($paper1 & 0x08);
		        		$paper2 = (ord($this->src[$attr2 + ($x & 0x0f)]) >> 3) & 0x0f;
		        		$ink2   = (ord($this->src[$attr2 + ($x & 0x0f)]) & 0x07) | ($paper2 & 0x08);
					}
					else {
						$attr1 = 256 + 12288 + $y*16;
						$attr2 = 256 + 12288 + 3072 + $y*16;
		        		$paper1 = (ord($this->src[$attr1 + $x - 8]) >> 3) & 0x0f;
		        		$ink1   = (ord($this->src[$attr1 + $x - 8]) & 0x07) | ($paper1 & 0x08);
		        		$paper2 = (ord($this->src[$attr2 + $x - 8]) >> 3) & 0x0f;
		        		$ink2   = (ord($this->src[$attr2 + $x - 8]) & 0x07) | ($paper2 & 0x08);
					}
				}
	      		else {
		        	$paper1 = (ord($this->src[$attr1 + $x]) >> 3) & 0x0f;
		        	$ink1   = (ord($this->src[$attr1 + $x]) & 0x07) | ($paper1 & 0x08);
		        	$paper2 = (ord($this->src[$attr2 + $x]) >> 3) & 0x0f;
		        	$ink2   = (ord($this->src[$attr2 + $x]) & 0x07) | ($paper2 & 0x08);
	      		}
	      			      		
	      		switch ($this->MGS_CharSize) {
	        		case 8:
			        	$new_src[12295 + ($y >> 3)*512 + $x*2] = chr($colors[$ink1]);
			        	$new_src[12296 + ($y >> 3)*512 + $x*2] = chr($colors[$paper1]);
			        	$new_src[24583 + ($y >> 3)*512 + $x*2] = chr($colors[$ink2]);
			        	$new_src[24584 + ($y >> 3)*512 + $x*2] = chr($colors[$paper2]);
			        	break;
	      			case 4:
			        	$new_src[12295 + ($y >> 2)*256 + $x*2] = chr($colors[$ink1]);
			        	$new_src[12296 + ($y >> 2)*256 + $x*2] = chr($colors[$paper1]);
			        	$new_src[24583 + ($y >> 2)*256 + $x*2] = chr($colors[$ink2]);
			        	$new_src[24584 + ($y >> 2)*256 + $x*2] = chr($colors[$paper2]);
	        			break;
	      			case 2:
			        	$new_src[12295 + ($y >> 1)*128 + $x*2] = chr($colors[$ink1]);
			        	$new_src[12296 + ($y >> 1)*128 + $x*2] = chr($colors[$paper1]);
			        	$new_src[24583 + ($y >> 1)*128 + $x*2] = chr($colors[$ink2]);
			        	$new_src[24584 + ($y >> 1)*128 + $x*2] = chr($colors[$paper2]);
	        			break;
	      			case 1:
		        		if ($x < 8 || $x > 23) {
				        	$new_src[12295 + ($y >> 3)*512 + $x*2] = chr($colors[$ink1]);
				        	$new_src[12296 + ($y >> 3)*512 + $x*2] = chr($colors[$paper1]);
				        	$new_src[24583 + ($y >> 3)*512 + $x*2] = chr($colors[$ink2]);
				        	$new_src[24584 + ($y >> 3)*512 + $x*2] = chr($colors[$paper2]);
		        		}
			        	else { 
				        	$new_src[12295 + $y*64 + $x*2] = chr($colors[$ink1]);
				        	$new_src[12296 + $y*64 + $x*2] = chr($colors[$paper1]);
				        	$new_src[24583 + $y*64 + $x*2] = chr($colors[$ink2]);
				        	$new_src[24584 + $y*64 + $x*2] = chr($colors[$paper2]);
			        	}
			        	
	        			break;
	      		}
	    	}
		}

		$this->src = $new_src;
	}
		
	/**
	 * Resize generated image and add border
	 * @param $frame
	 * @return unknown_type
	 */
	private function finalizeFrame($imtmp) {
		$full_width = $this->borders[$this->border][strval($this->output_scale)]['width'];
		$full_height = $this->borders[$this->border][strval($this->output_scale)]['height'];
		
		$image_left = intval(($full_width - $this->output_scale * 256)/2);
		$image_top = intval(($full_height - $this->output_scale * 192)/2);
		
		$frame = @imagecreatetruecolor($full_width, $full_height) or die("Cannot Initialize new GD image stream1");
		
		if ($this->options['isTransparent']) {
			imagecolortransparent($frame, $this->options['hiddenColor']);
		}
		
		imagefill($frame, 0, 0, $this->border_color);
		
		if ($this->output_scale != 1) {
			imagecopyresampled ($frame, $imtmp, $image_left, $image_top, 0, 0, $this->output_scale * 256, $this->output_scale * 192, 256, 192);
		}
		else {
			imagecopy ($frame, $imtmp, $image_left, $image_top, 0, 0, 256, 192);
		}
		
		imagedestroy($imtmp);
		return $this->applyFilters($frame);
	}
		
	private function applyFilters($im) {
		$width = imagesx($im);
		$height = imagesy($im);

		if ($this->filters['gaussian-blur']) {
			imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
		}
		elseif($this->filters['light-blur']) {
			$matrix = array(array(1,1,0), array(1,1,0), array(0,0,0));
			imageconvolution($im, $matrix, 4, 0);
		}
		
		if ($this->filters['interleave'] && $this->output_scale > 1) {
			if ($this->filters['interleaveLevel'] > 0) {
				$level = intval($this->filters['interleaveLevel'] / 100 * 127);
				imagealphablending($im, true);
				$interleave_color = imagecolorallocatealpha($im, 0, 0, 0,$level);
			}
			else
				$interleave_color = imagecolorallocate($im, 0, 0, 0);
			
			if ($this->output_scale == 2) {
				for($y = 0; $y < $height; $y += 2) {
					imageline ($im, 0, $y, $width, $y, $interleave_color);
				}
			}
			elseif ($this->output_scale == 3) {
				for($y = 0; $y < $height; $y += 3) {
					imageline ($im, 0, $y, $width, $y, $interleave_color);
					imageline ($im, 0, $y + 2, $width, $y + 2, $interleave_color);
				}
			}
		}

		return $im;
	}
	
	/**
	 * Return all available borders sizes
	 * 
	 * @return array
	 */
	function getBorders() {
		return $this->borders;
	}

	/**
	 * Set border size
	 *  
	 * @param $str string is none, small, media
	 * @return void
	 */
	function setBorder($str = '') {
		if (isset($this->borders[$str])) { 
			$this->border = $str;
		}
	}
	
	/**
	 * Set border color
	 * 
	 * @param $color integer from 0 to 7
	 * @return void
	 */
	function setBorderColor($color = 0) {
		if ($color >= 0 && $color <= 7) {
			$this->border_color = $this->colors[intval($color)];
		}
	}

	/**
	 * Return all available colors for 'showHiddenPixels' mode 
	 * 
	 * Return array with all available colors for image with transparent pixels. One color format:
	 * array('R' => int R, 'G' => int G, 'B' => int B)
	 * 
	 * @return array Colors array
	 */
	function getHiddenColors() {
		return $this->hidden_colors;
	}
	
	/**
	 *	Set color for 'showHiddenPixels' mode (INK=PAPER)
	 *
	 * @param int Index of color in 'hidden_colors' array. Or transparen if 0.
	 * @return int color
	 */
	function setHiddenColor($color_index = 0) {
		if ($color_index == 0 || !isset($this->hidden_colors[$color_index])) {
			$this->options['isTransparent'] = true;
		}
		else {
			$c = $this->hidden_colors[$color_index];
			$this->options['hiddenColor'] = $c['R'] * 0x10000 + $c['G'] * 0x100 + $c['B'];
			$this->options['isTransparent'] = false;
		}
	}
	
	/**
	 * Return all available palettes
	 * 
	 * @return array Palettes array
	 */
	function getPalettes() {
		return $this->palettes;
	}
	
	/**
	 * Set given palette as work palette
	 * 
	 * @param $str string palette name
	 * @return void
	 */
	function setPalette($str = '') {
		if ($this->input_type == 'bw-screen') {
			$str_palette = $this->palettes['Grey'];
		}
		elseif (isset($this->palettes[$str])) { 
			$str_palette = $this->palettes[$str];
		}
		elseif (!$str) {
			$str_palette = reset($this->palettes);
		}
		else {
			$str_palette = $str;
		}
		
		list($bright, $colors) = explode(':', $str_palette);
		
		list($red, $green, $blue) = explode(';', $colors);
		list($palette['ZZ'], $palette['ZN'], $palette['NN'], $palette['NB'], $palette['BB'], $palette['ZB']) = explode(',', $bright);
		
		list($palette['R11'], $palette['R12'], $palette['R13']) = explode(',', $red);
		list($palette['R21'], $palette['R22'], $palette['R23']) = explode(',', $green);
		list($palette['R31'], $palette['R32'], $palette['R33']) = explode(',', $blue);
		
		foreach($palette as &$val) {
			$val = hexdec(trim($val));			
		}
		
		$Sr = $palette['R11']+$palette['R12']+$palette['R13'];
		$Sg = $palette['R21']+$palette['R22']+$palette['R23'];
		$Sb = $palette['R31']+$palette['R32']+$palette['R33'];
		$S = max($Sr, $Sg, $Sb);
		
		$this->generateGigaColors($palette, $S);
		$this->generateColors($palette, $S);
		$this->generateColors8($palette, $S);
	}

	/**
	 * Return output type
	 * 
	 * @return string with 'png' or 'gif'
	 */
	function getOutputType() {
		return $this->output_type;
	}

	
	/**
	 * Force output type
	 * 
	 * @param $type string with 'png' or 'gif'
	 * @return void
	 */
	function setOutputType($type) {
		$type = strtolower($type);
		if ($type == 'png' || $type == 'gif') 
			$this->output_type = $type;
	}

	/**
	 * Set output image scale
	 * 
	 * @param $scale integer of 1, 2, 3
	 * @return void
	 */
	function setOutputScale($scale = 1) {
		if (in_array($scale, array(0.5,2,3))) {
			$this->output_scale = floatval($scale);
		}
	}

	/**
	 * Set option
	 * 
	 * @param $option_name
	 * @param $value
	 * @return void
	 */
	function setOption($option_name, $value = false) {
		if (isset($this->options[$option_name])) {
			$this->options[$option_name] = $value;
		}
	}
	
	/**
	 * Set output filter
	 * 
	 * @param string		Filter name
	 * @param unknown_type	Filter value
	 * @return void
	 */
	function setFilters($filter = '', $value = 0) {
		if ($filter == 'interleave') {
			$this->filters['interleave'] = true;
			$level = intval($value);
			if ($level < 0 || $level > 100) $level = 0;
			$this->filters['interleaveLevel'] = $level;
		}
		
		if ($filter == 'light-blur') {
			$this->filters['light-blur'] = ($value) ? true : false;
		}
		elseif ($filter == 'gaussian-blur') {
			$this->filters['gaussian-blur'] = ($value) ? true : false;
		}
	}
	
	/**
	 * Load source image
	 * 
	 * @param string SCR/IMG/MGS data
	 * @return string Type of image. If type not detected return  false
	 */
	function loadData($data) {
		if (!$data) return false;
		$this->src = $data;
		 
		return ($this->detectInputType()) ? $this->input_type : false;
	}

	/**
	 * 	Generate image and return it
	 * 
	 */
	function generate() {
		return $this->generateImage();
	}
	
	/**
	 * 	Generate image and show
	 * 
	 */
	function show() {
		$result = $this->generateImage();

		header("Pragma: no-cache");
	    if ($this->output_type == 'png') {
		    header("Content-type: image/png");
		    echo $result;
			exit;
	    }		
	    if ($this->output_type == 'gif') {
	        header ('Content-type: image/gif');
		    echo $result;
			exit;
        }
	}

	/**
	 * 	Generate image and force download
	 * 
	 */
	function download($filename) {
		$result = $this->generateImage();

	    if ($this->output_type == 'png') {
		    header("Content-type: image/png");
			header('Content-Disposition: attachment; filename="'.$filename.'.png"');
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".strlen($result));
			
		    echo $result;
			exit;
	    }		
	    if ($this->output_type == 'gif') {
	        header ('Content-type: image/gif');
			header('Content-Disposition: attachment; filename="'.$filename.'.gif"');
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".strlen($result));
			echo $result;
			exit;
        }
	}

	/**
	 * 	Generate image and save it on server
	 * 
	 */
	function save($path) {
		$result = $this->generateImage();
		
		$path .= '.'.$this->output_type;
    	if (!$handle = fopen($path, 'w')) return false; 

    	if (fwrite($handle, $result) === FALSE)	return false;
    	fclose($handle);
    	
    	return $path;
	}	
}
?>