<?php

use App\Exceptions\GQLException;

/**
 * 过滤多余文字，只留下 链接
 *
 * @param [type] $str
 * @return void
 * @author zengdawei
 */
function filterText($str)
{
    if (empty($str) || $str == '' || is_null($str)) {
        throw new GQLException('分享链接是空的，请检查是否有误噢');
    }

    $regex = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@';

    if (preg_match($regex, $str, $match)) {
        return $match;
    } else {
        throw new GQLException('分享链接失效了，请检查是否有误噢');
    }

}
