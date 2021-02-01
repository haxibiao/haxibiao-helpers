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
