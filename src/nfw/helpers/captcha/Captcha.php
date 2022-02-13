<?php
/**
* Captcha File
* Generates CAPRCHA Numbers Image
* @author Hadar Porat <hpman28@gmail.com>
* @version 1.5
* GNU General Public License (Version 2, June 1991)
*
* This program is free software; you can redistribute
* it and/or modify it under the terms of the GNU
* General Public License as published by the Free
* Software Foundation; either version 2 of the License,
* or (at your option) any later version.
*
* This program is distributed in the hope that it will
* be useful, but WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A
* PARTICULAR PURPOSE. See the GNU General Public License
* for more details.
*/

/**
* CaptchaNumbers Class
* @access public
* @author Hadar Porat <hpman28@gmail.com>
* @author nyuk (Template support)
* @version 1.51
*/

// $Id$

class CaptchaNumbers {
    private $length = 4;
    private $font = '1.ttf';
    private $size = 15;
    private $type = 'png';
    private $height = 40;
    private $width = 100;
    private $grid = 10;
    private $string = '';
    private $template = '';
    private $textcolor = false;
    private $offset = false;

    /**
    * @return void
    * @param int $length string length
    * @param int $size font size
    * @param String $type image type
    * @desc generate the main image
    */  
    function CaptchaNumbers($params = array()) {

        if (isset($params['length'])) $this->length = $params['length'];
        if (isset($params['size'])) $this->size = $params['size'];
        if (isset($params['type'])) $this->type = $params['type'];
        if (isset($params['font'])) $this->font = $params['font'];
        if (isset($params['textcolor'])) $this->textcolor = $params['textcolor'];
        if (isset($params['offset'])) $this->offset = $params['offset'];

		$this->font = str_replace('Captcha.php', '', __FILE__).$this->font;
		
		if (isset($params['template'])) {
			$this->template = str_replace('Captcha.php', '', __FILE__).'templates/'.$params['template'];
		}
		else {
			if (isset($params['width'])) {
				$this->width = $params['width'];
			}
			else { 
	        	$this->width = $this->length * $this->size + $this->grid;
			}
	        
	        $this->height = $this->size + (2 * $this->grid);
		}
		        
        $this->generateString();
    }

    /**
    * @return void
    * @desc display captcha image
    */      
    function display() {
        $this->sendHeader();
        $image = $this->generate();

        switch ($this->type) {
            case 'jpeg': imagejpeg($image); break;
            case 'png':  imagepng($image);  break;
            case 'gif':  imagegif($image);  break;
            default:     imagepng($image);  break;
        }
    }

    /**
    * @return Object
    * @desc generate the image
    */      
    function generate() {
    	if ($this->template) {
    		$path_parts = pathinfo($this->template);
			$extension = strtolower($path_parts['extension']);
			switch($extension) {
				case 'gif':
					$image = imagecreatefromgif($this->template); 
					break;
				case 'png':    		
					$image = imagecreatefrompng($this->template);
					break;
				case 'jpg':    		
				case 'jpeg':    		
					$image = imagecreatefromjpeg($this->template);
					break;
				default: 
					return false;
			}
    	}
    	else {
	        $image = ImageCreate($this->width, $this->height) or die("Cannot Initialize new GD image stream");
	        
	        // colors
	        $net_color = ImageColorAllocate($image, 200, 200, 200);
				
	        // grid
	        for ($i = $this->grid; $i < $this->width; $i+=$this->grid) ImageLine($image, $i, 0, $i, $this->height, $net_color);
	        for ($i = $this->grid; $i < $this->height; $i+=$this->grid) ImageLine($image, 0, $i, $this->width, $i, $net_color);
    	}

    	if ($this->textcolor) {
    		$stringcolor = ImageColorAllocate($image, $this->textcolor[0], $this->textcolor[1], $this->textcolor[2]);
    	}
    	else{
    		$stringcolor = ImageColorAllocate($image, 0, 0, 0);
    	}
    	
    	if ($this->offset) {
    		$x = $this->offset[0];
    		$y = $this->offset[1];
    	}
    	else {
    		$x = $this->grid;
    		$y = $this->size + $this->grid;
    	}
    	
        // make the text
        ImageTTFText($image, $this->size, 0, $x, $y, $stringcolor, $this->font, $this->getString());
        
        return $image;
    }


    /**
    * @return String
    * @desc generate the string
    */  
    function generateString() {
        $string = '';
        for ($i = 0; $i<$this -> length; $i++) {
            $string .= rand(0, 9);
        }

        $this->string = $string;
        return true;
    }


    /**
    * @return void
    * @desc send image header
    */
    function sendHeader() {
        header('Content-type: image/' . $this->type);
        
        // NO CACHE!
		// Date in the past
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");        
        //header("Cache-Control: no-cache");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);         
        header("Pragma: no-cache");
    }
    
    /**
    * @return String
    * @desc return the string
    */  
    function getString() {
        return $this -> string;
    }
}