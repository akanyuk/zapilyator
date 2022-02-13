<?php
/**
 * Check $_SERVER['REQUEST_URI'] for contains given string
 * 
 * @param string $url_part	search string
 * @param bool $inverse	inverse searchstring
 */
function page_is($url_part = '', $inverse = false) {
	foreach (is_array($url_part) ? $url_part : array($url_part) as $s) {
		if (!$s || !strstr($_SERVER['REQUEST_URI'], $s)) continue;
		
		return $inverse ? false : true;
	}
	
	return $inverse ? true : false;
}