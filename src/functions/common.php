<?php

use Illuminate\Support\Carbon;

function randomDate($begintime, $endtime = "", $is = true)
{
    $begin     = strtotime($begintime);
    $end       = $endtime == "" ? mktime() : strtotime($endtime);
    $timestamp = rand($begin, $end);
    return $is ? date("Y-m-d H:i:s", $timestamp) : $timestamp;
}

function create_date_array($begintime, $endtime, $num = 10)
{
    $i          = 0;
    $date_array = array();
    while ($i < $num) {
        $date                   = randomDate($begintime, $endtime);
        $date_array[$i]['time'] = $date;
        $i++;
    }
    sort($date_array);
    return $date_array;
}

function numberToReadable($number, $precision = 1, $divisors = null)
{
    $shorthand = '';
    $divisor   = pow(1000, 0);
    if (!isset($divisors)) {
        $divisors = [
            $divisor     => $shorthand, // 1000^0 == 1
            pow(1000, 1) => 'K', // Thousand
            pow(1000, 2) => 'M', // Million
            pow(1000, 3) => 'B', // Billion
        ];
    }
    foreach ($divisors as $divisor => $shorthand) {
        if (abs($number) < ($divisor * 1000)) {
            break;
        }
    }
    if ($divisor < pow(1000, 1)) {
        $precision = 0;
    }
    return number_format($number / $divisor, $precision) . $shorthand;
}

/**
 * 使用场景:无特定使用场景,通用!
 * 全局公共辅助函数
 */

/**
 * @deprecated 只有答赚网页用...
 */
function get_apk_link($version = "")
{
    $app = 'datizhuanqian';
    if (str_contains(env('APP_DOMAIN', ''), 'damei')) {
        $app = 'damei';
    }
    $env = env('APP_ENV');
    if ($env == 'prod' || $env == 'www') {
        $link = "http://dtzq-1251052432.cos.ap-shanghai.myqcloud.com/$app-release$version.apk";
    } else {
        $link = "http://dtzq-1251052432.cos.ap-shanghai.myqcloud.com/$app-$env" . $version . ".apk";
    }

    return $link;
}

//答赚网页用
function get_ios_apk_link()
{
    return config('app.ios_link');
}

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
    $environment = ['prod', 'production', 'www'];
    return app()->environment($environment);
}

function is_hotfix_env()
{
    return app()->environment('hotfix');
}

function is_local_env()
{
    return app()->environment('local');
}

function is_dev_env()
{
    return app()->environment('dev');
}
function is_prod()
{
    return app()->environment('prod');
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

function failed_response($message = '服务器开小差了...', $statusCode = 500)
{
    return response()->json([
        'status'  => 'FAILED',
        'code'    => $statusCode,
        'message' => $message,
    ], $statusCode);
}

function successful_response($data, $statusCode = 200)
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

// 获取用户设备品牌
function get_user_brand()
{
    return request()->header('referrer');
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

/**
 *
 * 随机算法
 *
 * 假设我们有这样一个数组[ 'withdraw01'=>5, 'withdraw02'=>5, 'withdraw03'=>10]
 * withdraw01概率25%，withdraw02奖概率25%，withdraw03奖概率50%
 *
 * @param $plucked
 * @return int|string|null
 */
function getRand($plucked)
{
    $luckId  = null;
    $sumRate = array_sum($plucked);
    foreach ($plucked as $key => $value) {
        $randNum = mt_rand(1, $sumRate);
        if ($randNum <= $value) {
            $luckId = $key;
            break;
        } else {
            $sumRate -= $value;
        }
    }
    return $luckId;
}

//传入一个数组，根据其key对应的value返回有权重的随机数
function countWeight($data)
{
    if (count($data) < 1) {
        return;
    }
    // 权重数值越高，被返回的概率越大
    $weight = 0;
    $temp   = array();

    foreach ($data as $key => $value) {
        $weight += $value;
        for ($i = 0; $i < $value; $i++) {
            $temp[] = $key; //放大数组
        }
    }

    $int    = mt_rand(0, $weight - 1); //获取一个随机数
    $result = $temp[$int];
    return $result;
}

//随机一个昵称
function randomName()
{
    $str          = file_get_contents(dirname(dirname(__FILE__)) . '/utils/Sensitive/names.txt');
    $str_encoding = mb_convert_encoding($str, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5'); //转换字符集（编码）
    $arr          = explode("\n", $str_encoding); //转换成数组

    //去空值
    $arr = array_filter($arr);
    //去重复
    $arr = array_unique($arr);

    // dd($rows[random_int(0, count($rows))]);
    return $arr[random_int(0, count($arr) - 1)] ?? "匿名用户";
}

//推送站点url喂给百度（提高收录效率）
function pushSeoUrl($urls, $api)
{
    $ch      = curl_init();
    $options = array(
        CURLOPT_URL            => $api,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => implode("\n", $urls),
        CURLOPT_HTTPHEADER     => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    return $result;
    //成功返回示例
    //     {
    //     "remain":99998,   当天剩余的可推送url条数
    //     "success":2,   成功推送的url条数
    //     "not_same_site":[],  由于不是本站url而未处理的url列表
    //     "not_valid":[],  不合法的url列表
    // }

}

/**
 * 返回字符”成功“才算成功，其他都是失败原因
 *
 * @param array $urls
 * @param string $token
 * @param string $domain
 * @return string
 */
function push_baidu($urls, $token, $domain, $proxy = null)
{
    // 格式化FormData
    $urls = array_map(function ($url) {
        return urlencode($url);
    }, $urls);
    $urls = implode('%0A', $urls);

    $firstPartOfParamer = http_build_query([
        'host'  => $domain,
        'token' => $token,
    ]);
    $paramers = $firstPartOfParamer . '&urls=' . $urls;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL            => 'http://tools.bugscaner.com/api/urltobaidu',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $paramers,
        CURLOPT_HTTPHEADER     => array(
            'Referer: http://tools.bugscaner.com/urltobaidu',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36',
        ),
    ));

    $result = curl_exec($curl);
    curl_close($curl);

    if (!$result) {
        return json_encode([
            'error'   => 401,
            'message' => 'baidu push error',
        ]);
    }
    /**
     * 格式化响应结果
     * bugscaner 推送响应参数说明
     *  status     推送状态，不为1都是失败
     *  remain_nbs 今天剩余的总额度条数
     *  user_allnb 本次一共提交了
     *  oknbs      本次推送成功条数
     *  valide_nb  有效的网址条数
     * baidu 推送响应参数说明
     *  success        成功推送的url条数
     *  remain        当天剩余的可推送url条数
     *  not_same_site 由于不是本站url而未处理的url列表
     *  not_valid     不合法的url列表
     */
    $result = json_decode($result, true);
    // status不为1都是推送失败
    if (data_get($result, 'status', 0) == 1) {
        $result = [
            'success'       => data_get($result, 'oknbs'),
            'remain'        => data_get($result, 'remain_nbs'),
            'not_same_site' => [],
            'not_valid'     => [],
        ];
    } else {
        $result = [
            'error'   => 401,
            'message' => 'baidu push error',
        ];
    }
    $result = json_encode($result);

    if (str_contains($result, "success")) {
        return "成功";
    }
    return $result;
}

/**
 * 推送MIP数据给神马搜索
 *
 * @param array $urls 地址数组
 * @param string $token 令牌
 * @param string $domain 域名
 * @param string $owner_email 站长邮箱
 * @return string
 */
function push_shenma($urls, $token, $domain, $owner_email)
{
    $api     = 'https://data.zhanzhang.sm.cn/push?site=' . $domain . '&user_name=' . $owner_email . '&resource_name=mip_add&token=' . $token;
    $ch      = curl_init();
    $options = array(
        CURLOPT_URL            => $api,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => implode("\n", $urls),
        CURLOPT_HTTPHEADER     => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    echo $result;
}

//使用方法类似random_int($minx,$max),返回该区间内保留两位小数点的随机浮点数。
function randFloat($min = 0, $max = 1)
{
    $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    return floatval(number_format($rand, 2));
}

function register_routes($path)
{
    $is_testing = false;
    try {
        $phpunit    = simplexml_load_file('phpunit.xml');
        $is_testing = !app()->environment('prod');
    } catch (Exception $ex) {
    }
    $files = [];
    get_allfiles($path, $files);
    foreach ($files as $apiFile) {
        if ($is_testing) {
            require $apiFile;
        } else {
            require_once $apiFile;
        }
    }
}
