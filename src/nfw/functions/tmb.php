<?php
/*
 * Генератор превью картинок из каталога `media` в соответствующем ему каталоге `tmb`
 * Размер превью определяется ключами --w% --h% в имени файла.
 * 
 * $options:
 * 		'media_class' 		- custom media class and path. `media` by default
 * 		'format' 			- `png`, `jpg`, `gif` or any other for same as source. `png` by default
 * 		'crop'				- Crop image to given size. `false` by default
 * 		'complementary'		- Complement image to given size. `false` by default
 * 		'complementary_transparent' - Transparent fill of complemented areaa
 * 		'force_generate'	- Re-generate thumbnail anyway
 * 		'filename' 			- force new filename
 */

function tmb($request, $tmb_width = 0, $tmb_height = 0, $options = array()) {
	$t = new _tmbGenerator($request, $tmb_width, $tmb_height, $options);
	return $t->error ? false : $t->generate();
}

class _tmbGenerator {
	var $error = false;
	var $media_class = 'media';
	var $format = 'png';
		
	function __construct($request, $tmb_width = 0, $tmb_height = 0, $options = array()) {
		// Save original request
		$this->request = $request;
		$this->tmb_width  = $tmb_width;
		$this->tmb_height = $tmb_height;
		$this->options = $options;
		
		if (is_array($request)) {
			if (!isset($request['url'])) {
				$this->error = true;
				return;
			}
			
			// Для `secure_storage` возвращаем в случае полного размера не урл, а физическое расположение картинки
			$this->url = isset($request['secure_storage']) && $request['secure_storage'] ? $request['fullpath'] : $request['url'];
		}
		else {
			$this->url = $request;
		}
		
		// Finding original file
		if (is_array($request) && isset($request['fullpath'])) {
			$fullpath = $request['fullpath'];
		}
		else {
			$fullpath = str_replace('//', '/', PROJECT_ROOT.parse_url($this->url, PHP_URL_PATH));
		}
		
		if (!file_exists($fullpath)) {
			$this->error = true;
			return;
		}
		
		if (isset($options['media_class'])) {
			$this->media_class = $options['media_class'];
		}

		$path_parts = pathinfo($fullpath);
		
		// Determine thumbnail filename
		
		if (isset($request['secure_storage']) && $request['secure_storage'] && isset($request['id'])) {
			$this->tmb_filename = sprintf("%08s", $request['id']);
		}
		elseif (isset($options['filename'])) {
			$this->tmb_filename = $options['filename'];
		}
		elseif (isset($request['id'])) {
			$this->tmb_filename = sprintf("%08s", $request['id']);
		}
		else {
			$this->tmb_filename = rawurldecode($path_parts['filename']);
		}
		
		if ($this->tmb_width) $this->tmb_filename .= '--w'.$this->tmb_width;
		if ($this->tmb_height) $this->tmb_filename .= '--h'.$this->tmb_height;
		
		if (isset($options['crop']) && $options['crop'])  {
			$this->tmb_filename .= '--crop';
		}
		elseif (isset($options['complementary']) && $options['complementary'])  {
			$this->tmb_filename .= '--cmp';
		}
		
		// Determine format and extension
		
		if (!$result = getimagesize($fullpath)) {
			$this->error = true;
			return false;
		}
		list($this->src_width, $this->src_height, $this->img_type) = $result;
		$this->img_type = str_replace('jpeg', 'jpg', image_type_to_extension($this->img_type, false));
		if (!in_array($this->img_type, array('png', 'jpg', 'gif'))) {
			$this->error = true;
			return;
		}
		
		if (isset($options['format'])) {
			if (in_array($options['format'], array('png', 'jpg', 'gif'))) {
				$this->format = $options['format'];
			}
			else {
				// Use same as source format
				$this->format = $this->img_type;
			}
		}
		$this->tmb_filename = $this->tmb_filename.'.'.$this->format;
		
		// Determine thumbnail full path
		
		if (is_array($request) && isset($request['tmb_dir'])) {
			$this->tmb_dir = $request['tmb_dir'];
			$this->tmb_fullpath = $this->tmb_dir.'/'.$this->tmb_filename;
		}
		else {
			$owner_class = preg_replace('/(.*\/'.$this->media_class.'\/)/', '', $path_parts['dirname']);
			$this->tmb_dir = NFW::i()->absolute_path.'/'.$this->media_class.'/'.$owner_class.'/tmb/';
			$this->tmb_fullpath = PROJECT_ROOT.$this->media_class.'/'.$owner_class.'/tmb/'.$this->tmb_filename;
		}
		

		$this->tmb_exist = file_exists($this->tmb_fullpath) && (!isset($options['force_generate']) || !$options['force_generate']) ? true : false;
		if ($this->tmb_exist) return;
		
		// Create source instance
		switch ($this->img_type) {
			case 'jpg':
				$this->src_img = @imagecreatefromjpeg($fullpath);
				break;
			case 'png':
				$this->src_img = @imagecreatefrompng($fullpath);
				break;
			case 'gif':
				$this->src_img = @imagecreatefromgif($fullpath);
				break;
		}
	}
	
	private function generateTmb() {
		// Determine new image dimension
		$tmb_max_width = isset(NFW::i()->cfg['media']['tmb_max_width']) ? NFW::i()->cfg['media']['tmb_max_width'] : 2048;
		$tmb_max_height = isset(NFW::i()->cfg['media']['tmb_max_height']) ? NFW::i()->cfg['media']['tmb_max_height'] : 2048;
		$max_width  = $this->tmb_width > 0 && $this->tmb_width < $tmb_max_width ? intval($this->tmb_width) : $tmb_max_width;
		$max_height  = $this->tmb_height > 0 && $this->tmb_height < $tmb_max_height ? intval($this->tmb_height) : $tmb_max_height;
		
		if ($max_width > $this->src_width) $max_width = $this->src_width;
		if ($max_height > $this->src_height) $max_height = $this->src_height;
		
		$ratio = 1;
		
		if ($max_width)
			$ratio = $max_width / $this->src_width;
		if ($max_height)
			$ratio = ($max_height / $this->src_height < $ratio) ? $max_height / $this->src_height : $ratio;
		
		$width  = intval($this->src_width * $ratio);
		$height = intval($this->src_height * $ratio);
		
		if (!$width) $width = 1;
		if (!$height) $height = 1;
		
		if ($ratio == 1) {
			// Show original image without resizing
			return $this->url;
		}

		// Create resized image
		switch ($this->format) {
			case 'jpg':
				$img = imagecreatetruecolor($width, $height);
				imagecopyresampled ($img, $this->src_img, 0,0,0,0, $width, $height, $this->src_width, $this->src_height);
				imagejpeg($img, $this->tmb_fullpath, media::JPEG_QUALITY);
				break;
			case 'png':
				$img = imagecreatetruecolor($width, $height);
				imagecopyresampled ($img, $this->src_img, 0,0,0,0, $width, $height, $this->src_width, $this->src_height);
				imagepng($img, $this->tmb_fullpath);
				break;
			case 'gif':
				$img = imagecreate($width, $height);
				imagecopyresampled ($img, $this->src_img, 0,0,0,0, $width, $height, $this->src_width, $this->src_height);
				imagegif($img, $this->tmb_fullpath);
				break;
		}
		
		imagedestroy($img);
		imagedestroy($this->src_img);
		
		return $this->tmb_dir.$this->tmb_filename;
	}
	
	private function generateTmbCrop() {
		if ($this->tmb_width / $this->src_width > $this->tmb_height / $this->src_height) {
			$ratio = $this->tmb_width / $this->src_width;
		}
		else {
			$ratio = $this->tmb_height / $this->src_height;
		}
	
		$dst_width  = intval($this->src_width * $ratio);
		$dst_height = intval($this->src_height * $ratio);
	
		if (!$dst_width) $dst_width = 1;
		if (!$dst_height) $dst_height = 1;
	
		// Calculate croping offset
		$offset_x = ($this->tmb_width - $dst_width)/2;
		$offset_y = ($this->tmb_height - $dst_height)/2;
	
		// Create resized image
		switch ($this->format) {
			case 'jpg':
				$img = imagecreatetruecolor($this->tmb_width, $this->tmb_height);
				imagecopyresampled ($img, $this->src_img, $offset_x, $offset_y, 0, 0, $dst_width, $dst_height, $this->src_width, $this->src_height);
				imagejpeg($img, $this->tmb_fullpath, media::JPEG_QUALITY);
				break;
			case 'png':
				$img = imagecreatetruecolor($this->tmb_width, $this->tmb_height);
				imagecopyresampled ($img, $this->src_img, $offset_x, $offset_y, 0, 0, $dst_width, $dst_height, $this->src_width, $this->src_height);
				imagepng($img, $this->tmb_fullpath);
				break;
			case 'gif':
				$img = imagecreate($this->tmb_width, $this->tmb_height);
				imagecopyresampled ($img, $this->src_img, $offset_x, $offset_y, 0, 0, $dst_width, $dst_height, $this->src_width, $this->src_height);
				imagegif($img, $this->tmb_fullpath);
				break;
		}
	
		imagedestroy($img);
		imagedestroy($this->src_img);
	
		return $this->tmb_dir.$this->tmb_filename;
	}
	
	private function generateTmbComplement() {
		// Determine new image dimension
		$max_width = $this->tmb_width > $this->src_width ? $this->src_width : $this->tmb_width;
		$max_height  = $this->tmb_height > $this->src_height ? $this->src_height : $this->tmb_height;
		
		$ratio = $max_width / $this->src_width;
		$ratio = ($max_height / $this->src_height < $ratio) ? $max_height / $this->src_height : $ratio;
		
		$dst_width  = intval($this->src_width * $ratio);
		$dst_height = intval($this->src_height * $ratio);
		
		if (!$dst_width) $dst_width = 1;
		if (!$dst_height) $dst_height = 1;
		
		// Complements image
		$offset_x = ($this->tmb_width - $dst_width)/2;
		$offset_y = ($this->tmb_height - $dst_height)/2;
		
		// Create resized image
		switch ($this->format) {
			case 'jpg':
				$img = imagecreatetruecolor($this->tmb_width, $this->tmb_height);
				imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
				imagecopyresampled ($img, $this->src_img, $offset_x, $offset_y, 0, 0, $dst_width, $dst_height, $this->src_width, $this->src_height);
				imagejpeg($img, $this->tmb_fullpath, media::JPEG_QUALITY);
				break;
			case 'png':
				$img = imagecreatetruecolor($this->tmb_width, $this->tmb_height);
				
				if (isset($this->options['complementary_transparent']) && $this->options['complementary_transparent']) { 
					imagefill($img, 0, 0, imagecolorallocate($img, 255, 254, 255));
					imagecolortransparent($img, imagecolorallocate($img, 255, 254, 255));
				}
				else {
					imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
				}
				
				imagecopyresampled ($img, $this->src_img, $offset_x, $offset_y, 0, 0, $dst_width, $dst_height, $this->src_width, $this->src_height);
				imagepng($img, $this->tmb_fullpath);
				break;
			case 'gif':
				$img = imagecreate($this->tmb_width, $this->tmb_height);
				imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
				imagecopyresampled ($img, $this->src_img, $offset_x, $offset_y, 0, 0, $dst_width, $dst_height, $this->src_width, $this->src_height);
				imagegif($img, $this->tmb_fullpath);
				break;
		}
		
		imagedestroy($img);
		imagedestroy($this->src_img);
		
		return $this->tmb_dir.$this->tmb_filename;
	}
	
	function generate() {
		if ($this->tmb_exist) return $this->tmb_dir.$this->tmb_filename;
		
		$complementary = isset($this->options['complementary']) && $this->options['complementary'] ? true : false;
		$crop = isset($this->options['crop']) && $this->options['crop'] ? true : false;
		
		if (!$this->tmb_width && !$this->tmb_height && !$complementary && !$crop) return $this->url;

		if ($crop) {
			// Make complementary instead crop if src image too small
			if ($this->tmb_width >= $this->src_width && $this->tmb_height >= $this->src_height) {
				return $this->generateTmbComplement();
			}
			
			return $this->generateTmbCrop();
		}
		else if ($complementary) {
			return $this->generateTmbComplement();
		}  
		else {
			return $this->generateTmb();			
		}
	}
}