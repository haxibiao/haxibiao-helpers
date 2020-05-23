<?php

use haxibiao\helper\QcloudUtils;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * 使用场景:无特定使用场景,通用!
 * 全局公共辅助函数
 */
function get_domain_key()
{
    return str_replace('.', '_', get_domain());
}

function get_cn_weekday($carbon)
{
    if ($carbon->dayOfWeek == Carbon::SUNDAY) {
        return '周日';
    }
    return "周" . $carbon->dayOfWeek;
}

function is_json($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function is_staging_env()
{
    return config('app.env') == 'staging';
}

function is_testing_env()
{
    return config('app.env') == 'testing';
}

function is_prod_env()
{
    $environment = ['prod', 'production','hotfix'];
    return in_array(config('app.env'), $environment);
}

function is_night()
{
    return date('H') >= 21 || date('H') <= 8;
}

//识别是否凌晨最早的几分钟
function is_moring($minute = 2)
{
    return date('H') == 0 && date('i') <= $minute;
}

function get_apk_link($version = "")
{
    $app = 'datizhuanqian';
    if (str_contains(env('APP_DOMAIN'), 'damei')) {
        $app = 'damei';
    }
    $env = env('APP_ENV');
    if ($env == 'prod') {
        $link = "http://dtzq-1251052432.cos.ap-shanghai.myqcloud.com/$app-release$version.apk";
    } else {
        $link = "http://dtzq-1251052432.cos.ap-shanghai.myqcloud.com/$app-$env" . $version . ".apk";
    }

    return $link;
}

function get_apk_name()
{
    $name = '正式';
    $env  = env('APP_ENV');
    if ($env == 'staging') {
        $name = "内测";
    }
    if ($env == 'dev') {
        $name = "开发";
    }
    if ($env == 'hotfix') {
        $name = "hotfix";
    }
    return $name;
}

function get_cos_url($path, $disk = null)
{
    $cosUrl = config('app.cos_url') . '/';
    switch ($disk) {
        case 'apks':
            $cosUrl .= 'apks/';
            break;
        default:
            $cosUrl .= 'storage/app/';
            break;
    }

    //返回默认图片
    if (empty($path)) {
        return $cosUrl . 'avatars/avatar.png';
    }

    $cosUrl .= $path;
    return $cosUrl;
}

function is_cos_url($path)
{
    return strpos($path, config('app.cos_url')) !== false;
}

function time_ago($time)
{
    if (isset($time->timestamp)) {
        $time = $time->timestamp;
    } else {
        return null;
    }

    $now = time() - $time;
    if ($now < 10) {
        return '刚刚';
    }

    $timestamps = [
        12 * 30 * 24 * 60 * 60 => '年前',
        30 * 24 * 60 * 60      => '个月前',
        7 * 24 * 60 * 60       => '周前',
        24 * 60 * 60           => '天前',
        60 * 60                => '小时前',
        60                     => '分钟前',
        1                      => '秒前',
    ];
    foreach ($timestamps as $timestamp => $timeAgo) {
        $time = $now / $timestamp;
        if ($time >= 1) {
            //向下取整
            $time = floor($time);
            return $time . $timeAgo;
        }
    }
}

/**
 * @Author      XXM
 * @DateTime    2019-02-10
 * @description [将字节转换成文件大小单位]
 * @param       [type]        $byteSize [description]
 * @return      [type]                  [description]
 */
function formatBytes($byteSize)
{
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $byteSize >= 1024 && $i < 4; $i++) {
        $byteSize /= 1024;
    }

    return round($byteSize, 2) . $units[$i];
}

function is_phone_number($value)
{
    return preg_match('/^1\d{10}$/', $value);
}

function account(string $login)
{
    $map = [
        'email' => filter_var($login, FILTER_VALIDATE_EMAIL),
        'phone' => is_phone_number($login),
    ];

    foreach ($map as $field => $value) {
        if ($value) {
            return $field;
        }
    }
    return null;
}

function is_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function random_str($length)
{
    return str_pad(mt_rand(0, 999999), $length, "0", STR_PAD_BOTH);
}

function conversionToRate($value)
{
    if ($value <= 1) {
        return ($value * 100) . '%';
    }
    return $value . '%';
}

function public_storage_path($path)
{
    return storage_path('app/public/' . $path);
}

function array_reverse_keys($arr, $arrBak = [])
{
    $result = [];
    foreach ($arr as $key => $value) {
        $result[] = $key;
        if (is_array($value)) {
            $result = array_reverse_keys($value, $result);
        }
    }
    return array_merge($result, $arrBak);
}

function failed_response($statusCode = 500, $message = '服务器开小差了...')
{
    return response()->json([
        'status'  => 'FAILED',
        'code'    => $statusCode,
        'message' => $message,
    ], $statusCode);
}

function successful_response($statusCode = 200, $data)
{
    return response()->json([
        'status' => 'SUCCESS',
        'code'   => $statusCode,
        'data'   => $data,
    ], $statusCode);
}

if (!function_exists('mb_str_split')) {
    function mb_str_split($str)
    {
        return preg_split('/(?<!^)(?!$)/u', $str);
    }
}

function checkStrRepeatRate($str)
{
    $length = mb_strlen($str, 'utf8');
    if ($length <= 3) {
        return 0;
    }

    $strArr      = mb_str_split($str);
    $countValues = array_count_values($strArr);
    rsort($countValues);
    $sum = array_sum((array_slice($countValues, 0, 3)));

    return $sum / $length * 100;
}

function hide_phone($str)
{
    return substr_replace($str, '****', 3, 4);
}

function genrate_uuid($suffix = '')
{
    return date('YmdHis') . uniqid() . '.' . $suffix;
}

function sort_string($string)
{
    $stringParts = str_split($string);
    sort($stringParts);
    return implode('', $stringParts);
}

function get_os_version()
{
    return request()->header('systemVersion') ?? request()->get('systemVersion');
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

function ssl_url($url)
{
    if (starts_with($url, 'https')) {
        return $url;
    }
    if (!starts_with($url, 'http')) {
        return secure_url($url);
    }
    return str_replace("http", "https", $url);
}

function trim_https($url)
{
    //替换URL协议
    if (starts_with($url, 'https:')) {
        return str_replace(['https:'], 'http:', $url);
    }
    return $url;
}

function get_site_domain()
{
    return env('APP_DOMAIN', get_domain());
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

function processVideo($video)
{
    $video->syncVodProcessResult();
    //如果还没有截图 就重新执行调用截图接口
    if (!$video->cover && !empty($video->qcvod_fileid)) {
        $duration = $video->duration > 9 ? 9 : $video->duration;
        QcloudUtils::makeCoverAndSnapshots($video->qcvod_fileid, $duration);
    }
}

function dropIndexIfExist($tableName, $indexName)
{
    Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
        $sm            = Schema::getConnection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($tableName);

        if ($doctrineTable->hasIndex($indexName)) {
            $table->dropIndex($indexName);
        }
    });
}
