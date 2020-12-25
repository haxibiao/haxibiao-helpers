<?php

use Haxibiao\Helpers\utils\QcloudUtils;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Facades\Agent;

//FIXME: 需要重构到 haxibiao-media
/**
 * @deprecated
 */
function processVideo($video)
{
    $video->syncVodProcessResult();
    //如果还没有截图 就重新执行调用截图接口
    if (!$video->cover && !empty($video->qcvod_fileid)) {
        $duration = $video->duration > 9 ? 9 : $video->duration;
        QcloudUtils::makeCoverAndSnapshots($video->qcvod_fileid, $duration);
    }
}

function cdnurl($path)
{
    $path = "/" . $path;
    $path = str_replace('//', '/', $path);
    return "http://" . env('COS_DOMAIN') . $path;
}

/**
 * 获取文件大小信息
 * @param $bytes
 * @return string
 */
function formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' MB';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

function fix_article_body_images($body)
{
    $preg = '/<img.*?src="(.*?)".*?>/is';

    preg_match_all($preg, $body, $match);

    if (!empty($match[1]) && !str_contains($body, 'haxibiao')) {
        foreach ($match[1] as $image_url) {
            $body = str_replace("$image_url", "https://haxibiao.com$image_url", $body);
        }
    }

    return $body;
}

function base_uri()
{
    $http    = Request::secure() ? 'https://' : 'http://';
    $baseUri = $http . Request::server('HTTP_HOST');
    return $baseUri;
}

function ajaxOrDebug()
{
    return request()->ajax() || request('debug');
}

function isMobile()
{
    return Agent::isMobile();
}

function isDeskTop()
{
    return Agent::isDesktop();
}

function isRobot()
{
    return Agent::isRobot();
}

function isChrome()
{
    return strtolower(Agent::browser()) == 'chrome';
}

function isPhone()
{
    return true;
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

function smartPager($qb, $pageSize = 10)
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

function match_str($str)
{
    return true;
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

function getAppStore()
{
    return request()->header('referer') ?: 'haxibiao';
}

function isHuawei()
{
    return strtolower(getAppStore()) == "huawei";
}

function isVivo()
{
    return strtolower(getAppStore()) == "vivo";
}

function getAppOS()
{
    return request()->header('os') ?: 'android';
}

function isAndroidApp()
{
    return getAppOS() == "android";
}

function getOsSystemVersion()
{
    $os             = getAppOS();
    $os_version     = request()->header('systemVersion') ?? "未知版本";
    $os_and_version = $os . " " . $os_version;
    return $os_and_version;
}

function isAndroid10()
{
    return isAndroidApp() && str_contains(getOsSystemVersion(), "android 10");
}

function getAppBuild()
{
    return request()->header('build') ?: request()->get('build');
}

function getAppVersion()
{
    return request()->header('version') ?: request()->get('version');
}

function getDeviceBrand()
{
    return request()->header('brand') ?: request()->get('brand');
}

//目前只有com.dianmoge 1.6版本开始传入这个header
function getAppId()
{
    return request()->header('appid') ?: request()->get('appid');
}

function is_giql()
{
    $referer = request()->header("referer") ?? '';
    return str_contains($referer, '/graphiql?');
}

/**
 * @Author      XXM
 * @DateTime    2019-03-02
 * @description [获取来源]
 * @return      [type]        [description]
 */
function get_referer()
{
    $referer = request()->header('referer') ?? request()->get('referer', 'unknown');
    //官方正式版也标注渠道为 APK
    if ($referer == 'null' || $referer == 'undefined') {
        $referer = 'unknown';
    }
    return $referer;
}

function isTencent()
{
    return get_referer() == "tencent";
}

//获取APP的用户位置
function get_app_position()
{
    $position = request()->header('position') ?? request()->get('position');
    $position = !empty($position) ? explode(',', $position) : null;
    if (!empty($position)) {
        for ($i = 0, $count = count($position); $i < $count; $i++) {
            $position[$i] = intval($position[$i]);
        }
    }
    return $position;
}

function get_app_ip()
{
    return request()->header('ip') ?? request()->get('ip');
}

function get_os()
{
    return request()->header('os') ?? request()->get('os');
}

function get_appid()
{
    return request()->header('appid') ?? request()->get('appid');
}

function get_device_id()
{
    //这个是前端返回的设备唯一ID
    return request()->header('uniqueId') ?? request()->get('uniqueId');
}

function get_user_agent()
{
    return request()->header('userAgent') ?? request()->get('userAgent');
}

function project_is_dtzq()
{
    return str_contains(config('app.name'), 'datizhuanqian');
}

function getLatestAppVersion()
{
    return '2.8';
}

function get_domain()
{
    if ($host = request()->getHost()) {
        $host = str_replace("l.", "", $host);
        $host = str_replace("www.", "", $host);
        return $host;
    }

    return env('APP_DOMAIN');
}

//获取访客IP
function getIp()
{
    $ip = null;
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } else if (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } else if (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    }

    return $ip;
}

//1.5+版本可以看到待审题
function appCanReview()
{
    $can = true; //默认gql调试能审题
    if ($version = getAppVersion()) {
        $can = $version >= '1.5.0';
    }
    return $can;
}

function hasBadWords($text)
{
    try {
        $badWords      = file_get_contents(base_path('filter-question-keywords.json'));
        $badWordsArray = json_decode($badWords, true);
    } catch (\Exception $ex) {
        return false;
    }

    foreach ($badWordsArray as $badWord) {
        if (strpos($text, $badWord) !== false) {
            return true;
        }
    }
}

function get_os_version()
{
    return request()->header('systemVersion') ?? request()->get('systemVersion');
}

function get_site_domain()
{
    return env('APP_DOMAIN', get_domain());
}

function get_ip()
{
    $ip = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
        foreach ($matches[0] as $xip) {
            if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                $ip = $xip;
                break;
            }
        }
    }
    return $ip;
}
