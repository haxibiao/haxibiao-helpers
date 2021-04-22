<?php

/**
 * 提取HTML中的所有图片链接
 */
function extract_image_urls_from_html($string) {
	$doc = new DOMDocument();
	$doc->loadHTML($string);
	$xml = simplexml_import_dom($doc);
	$tags = $xml->xpath('//img');

	$imageUrls = [];
	foreach ($tags as $tag)
	{
		$imageUrls[] = $tag['src']->__toString();
	}
	return $imageUrls;
}
