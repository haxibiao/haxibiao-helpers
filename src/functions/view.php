<?php

use App\Image;
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

function link_source_css($category)
{
    $cate_css_path = '/cssfix/' . $category->id . '.css';
    if (file_exists(public_path($cate_css_path))) {
        return '<link rel="stylesheet" href="' . $cate_css_path . '">';
    }
    return '';
}

function is_weixin_editing()
{
    return Cookie::get('is_weixin_editing', false) || Request::get('is_weixin');
}

function get_article_url($article)
{
    $url = "/article/" . $article->id;
    if ($article->target_url) {
        $url = $article->target_url;
    }
    return $url;
}

function parse_image($body, $environment = null)
{
    //检测本地或GQL没图的时候取线上的
    $environment = $environment ?: \App::environment('local');

    if ($environment) {
        $pattern_img = '/<img(.*?)src=\"(.*?)\"(.*?)>/';
        preg_match_all($pattern_img, $body, $matches);
        $imgs = $matches[2];
        foreach ($imgs as $img) {
            $image = Image::where('path', $img)->first();
            if ($image) {
                $body = str_replace($img, $image->url, $body);
            }
        }
    }
    return $body;
}

function parse_video($body)
{
    //TODO:: [视频的尺寸还是不完美，后面要获取到视频的尺寸才好处理, 先默认用半个页面来站住]
    $pattern_img_video = '/<img src=\"\/storage\/video\/thumbnail_(\d+)\.jpg\"([^>]*?)>/iu';
    if (preg_match_all($pattern_img_video, $body, $matches)) {
        foreach ($matches[1] as $i => $match) {
            $img_html = $matches[0][$i];
            $video_id = $match;

            $video = Video::find($video_id);
            if ($video) {
                $video_html = '<div class="row"><div class="col-md-6"><div class="embed-responsive embed-responsive-4by3"><video class="embed-responsive-item" controls poster="' . $video->coverUrl . '"><source src="' . $video->url . '" type="video/mp4"></video></div></div></div>';
                $body       = str_replace($img_html, $video_html, $body);
            }
        }
    }
    return $body;
}

function get_qq_pic($qq)
{
    return 'https://q.qlogo.cn/headimg_dl?bs=qq&dst_uin=' . $qq . '&src_uin=qq.com&fid=blog&spec=100';
}

function get_qzone_pic($qq)
{
    return 'https://qlogo2.store.qq.com/qzonelogo/' . $qq . '/1/' . time();
}

function match($str)
{
    return true;
}

function get_top_nav_bg()
{
    if (get_domain() == 'dianmoge.com') {
        return 'background-color: #000000';
    }
    if (get_domain() == 'dongmeiwei.com') {
        return 'background-color: #9d2932';
    }
    if (get_domain() == 'ainicheng.com') {
        return 'background-color: #3b5795';
    }
    if (get_domain() == 'qunyige.com') {
        return 'background-color: #f796c9';
    }

    return '';
}

function get_top_nav_color()
{
    if (get_domain() == 'ainicheng.com') {
        return 'color: white';
    }
    if (get_domain() == 'dianmoge.com') {
        return 'color: white';
    }
    if (get_domain() == 'dongmeiwei.com') {
        return 'color: white';
    }
    return '';
}

function get_full_url($path)
{
    if (empty($path)) {
        return '';
    }
    if (starts_with($path, 'http')) {
        return $path;
    }
    return env('APP_URL') . $path;
}
