<?php

use App\Exceptions\UserException;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

function getLatestAppVersion()
{
    return '2.8';
}

function get_domain()
{
    return env('APP_DOMAIN');
}

function small_logo()
{
    return '/picture/logo.png';
}

//兼容网页
function qrcode_url()
{
    $apkUrl = "http://datizhuanqian.com/download"; //TODO: env?
    $logo   = small_logo();
    $qrcode = QrCode::format('png')->size(250)->encoding('UTF-8');
    $qrcode->merge(public_path($logo), .1, true);
    $qrcode = $qrcode->generate($apkUrl);
    $path   = base64_encode($qrcode);
    return $path;
}

//检查是否备案期间
function isRecording()
{
    return false;
}

/**
 * 使用场景:主要提供于APP设计 用于GQL中调用或 restful api
 * APP全局辅助函数
 */
function checkUser()
{
    try {
        $user = getUser();
    } catch (\Exception $ex) {
        return null;
    }

    return $user;
}

//获取当前用户
function getUser($throw = true)
{
    //已登录
    if (Auth::check()) {
        return Auth::user();
    }

    //APP的场景
    $user = request()->user();
    if (blank($user)) {

        //兼容passport guard
        $token = request()->bearerToken();

        //兼容我们自定义token方式
        if (blank($token)) {
            $token = request()->header('token') ?? request()->get('token');
        }
        if ($token) {
            $user = User::where('api_token', $token)->first();
        }

        //调试旧/graphiql 的场景
        if (is_giql() && !$user) {
            if ($user_id = Cache::get('giql_uid')) {
                $user = User::find($user_id);
            }
        }

        throw_if(is_null($user) && $throw, UserException::class, '客户端还没登录...');

        //授权,减少重复查询
        if ($user) {
            Auth::login($user, true);
        }

    }
    return $user;
}

//获取当前用户ID
function getUserId()
{
    return getUser()->id;
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

function getAppStore()
{
    return request()->header('referrer') ?: 'haxibiao';
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
    return str_contains(request()->header("referer"), '/graphiql?');
}

/**
 * @Author      XXM
 * @DateTime    2019-03-02
 * @description [获取来源]
 * @return      [type]        [description]
 */
function get_referer()
{
    $referrer = request()->header('referrer') ?? request()->get('referrer');

    //官方正式版也标注渠道为 APK
    if ($referrer == 'null' || $referrer == 'undefined') {
        $referrer = 'unknown';
    }
    return $referrer;
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
