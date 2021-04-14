<?php

/**
 * 包含中文文本
 *
 * @param string $str
 * @return bool
 */
function contains_chinese_text($str)
{
    return preg_match("/[\x7f-\xff]/", $str);
}

/**
 * 清理字符串中的HTML字符
 */
function str_purify($string) {

	// ----- remove HTML TAGs -----
	$string = preg_replace ('/<[^>]*>/', ' ', $string);

	// ----- remove control characters -----
	$string = str_replace("\r", '', $string);    // --- replace with empty space
	$string = str_replace("\n", ' ', $string);   // --- replace with space
	$string = str_replace("\t", ' ', $string);   // --- replace with space
	$string = str_replace("&lt;", '<', $string);
	$string = str_replace("&gt;", '>', $string);
	$string = str_replace("&amp;", '&', $string);
	$string = str_replace("&nbsp;", ' ', $string);

	// ----- remove multiple spaces -----
	$string = trim(preg_replace('/ {2,}/', ' ', $string));

	return $string;
}
