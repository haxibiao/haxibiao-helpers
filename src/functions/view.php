<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;

function ajaxOrDebug()
{
    return request()->ajax() || request('debug');
}

//判断是否是移动端访问
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false; // 找不到为flase,否则为TRUE
    }
    // 判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'mobile',
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        // 协议法，因为有可能不准确，放到最后判断
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

function isDesktop()
{
    return !isMobile();
}

function isRobot()
{
    //TODO: 简单修复 agent 类不require 的错误，日后完善agent检测
    return false;
    // return \Agent::isRobot();
}

function get_active_css($path, $full_match = 0)
{
    $active = '';
    if (Request::path() == '/' && $path == '/') {
        $active = 'active';
    } else if (starts_with(Request::path(), $path)) {
        $active = 'active';
    }
    if ($full_match) {
        if (Request::path() == $path) {
            $active = 'active';
        }
    }
    return $active;
}

function canEdit($content)
{
    return checkEditor() || $content->isSelf();
}

function diffForHumansCN($time)
{
    if ($time instanceof Carbon) {
        $humanStr = $time->diffForHumans();
        $humanStr = str_replace('from now', '以后', $humanStr);
        $humanStr = str_replace('ago', '前', $humanStr);
        $humanStr = str_replace('seconds', '秒', $humanStr);
        $humanStr = str_replace('second', '秒', $humanStr);
        $humanStr = str_replace('minutes', '分钟', $humanStr);
        $humanStr = str_replace('minute', '分钟', $humanStr);
        $humanStr = str_replace('hours', '小时', $humanStr);
        $humanStr = str_replace('hour', '小时', $humanStr);
        $humanStr = str_replace('days', '天', $humanStr);
        $humanStr = str_replace('day', '天', $humanStr);
        $humanStr = str_replace('weeks', '周', $humanStr);
        $humanStr = str_replace('week', '周', $humanStr);
        $humanStr = str_replace('months', '月', $humanStr);
        $humanStr = str_replace('month', '月', $humanStr);
        $humanStr = str_replace('years', '年', $humanStr);
        $humanStr = str_replace('year', '年', $humanStr);
        return $humanStr;
    }
    return $time;
}

function smartPager($qb, $pageSize)
{
    return isMobile() ? $qb->simplePaginate($pageSize) : $qb->paginate($pageSize);
}

function get_submit_status($submited_status, $isAdmin = false)
{
    $submit_status = $isAdmin ? '收录' : '投稿';
    switch ($submited_status) {
        case '待审核':
            $submit_status = '撤回';
            break;
        case '已收录':
            $submit_status = '移除';
            break;
        case '已拒绝':
            $submit_status = '再次投稿';
            break;
        case '已撤回':
            $submit_status = '再次投稿';
            break;
        case '已移除':
            $submit_status = '再次收录';
            break;
    }

    return $submit_status;
}

function count_words($body)
{
    $body_text = strip_tags($body);
    preg_match_all('/[\x{4e00}-\x{9fff}]+/u', strip_tags($body), $matches);
    $str        = implode('', $matches[0]);
    $body_count = strlen(strip_tags($body)) - strlen($str) / 3 * 2;
    return $body_count;
}

function user_id()
{
    return Auth::check() ? Auth::user()->id : false;
}

//TODO: ivan:需要重构到repo
function is_follow($type, $id)
{
    return Auth::check() ? Auth::user()->isFollow($type, $id) : false;
}

function checkEditor()
{
    return Auth::check() && Auth::user()->checkEditor();
}

function get_polymorph_types($type)
{
    return ends_with($type, 's') ? $type : $type . 's';
}

function is_in_app()
{
    return Cookie::get('is_in_app', false) || Request::get('in_app');
}
