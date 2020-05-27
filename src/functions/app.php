<?php

use App\Article;
use App\Exceptions\GQLException;
use App\Exceptions\UserException;
use App\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

function get_user_agent()
{
    return request()->header('userAgent') ?? request()->get('userAgent');
}

function project_is_dtzq()
{
    return Str::contains(config('app.name'), 'datizhuanqian');
}

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
    if (!class_exists("App\Aso")) {
        return null;
    }

    if (project_is_dtzq()) {
        return '/picture/logo.png';
    }

    //FIXME: helper:install 应该创建一些APP需要的基础表，并seed

    $logo = \App\Aso::getValue('下载页', 'logo');
    if (empty($logo)) {
        return '/logo/' . env('APP_DOMAIN') . '.small.png';
    } else {
        return $logo;
    }
}

//兼容网页
function qrcode_url()
{

    if (project_is_dtzq()) {
        $apkUrl = "http://datizhuanqian.com/download"; //TODO: env?
    } else {
        $apkUrl = "\App\Aso"::getValue('下载页', '安卓地址');
    }
    $logo   = small_logo();
    $qrcode = QrCode::format('png')->size(250)->encoding('UTF-8');
    if (str_contains($logo, env('COS_DOMAIN'))) {
        $qrcode->merge($logo, .1, true);
    } else {
        if (file_exists(public_path($logo))) {
            $qrcode->merge(public_path($logo), .1, true);
        }
    }
    $qrcode = $qrcode->generate($apkUrl);
    return base64_encode($qrcode);
}

//检查是否备案期间
function isRecording()
{
    if (!class_exists("App\AppConfig")) {
        return null;
    }

    $config = \App\AppConfig::where([
        'group' => 'record',
        'name'  => 'web',
    ])->first();
    if ($config && $config->state === \App\AppConfig::STATUS_ON) {
        return true;
    }
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
    if (auth('api')->user()) {
        return auth('api')->user();
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

function cdnurl($path)
{
    $path = "/" . $path;
    $path = str_replace('//', '/', $path);
    return "http://" . env('COS_DOMAIN') . $path;
}

function is_prod()
{
    return env('APP_ENV') == 'prod';
}

/**
 * 首页的文章列表
 * @return collection([article]) 包含分页信息和移动ＶＵＥ等优化的文章列表
 */
function indexArticles()
{
    $qb = Article::from('articles')
        ->with('user')->with('category')
        ->exclude(['body', 'json'])
        ->where('status', '>', 0)
        ->whereNull('source_url')
        ->whereNotNull('category_id')
        ->orderBy('updated_at', 'desc');
    $total    = count($qb->get());
    $articles = $qb->offset((request('page', 1) * 10) - 10)
        ->take(10)
        ->get();

    //过滤置顶的文章
    $stick_article_ids = array_column(get_stick_articles('发现'), 'id');
    $filtered_articles = $articles->filter(function ($article, $key) use ($stick_article_ids) {
        return !in_array($article->id, $stick_article_ids);
    })->all();

    $articles = [];
    foreach ($filtered_articles as $article) {
        $articles[] = $article;
    }

    //移动端，用简单的分页样式
    if (isMobile()) {
        $articles = new Paginator($articles, 10);
        $articles->hasMorePagesWhen($total > request('page') * 10);
    } else {
        $articles = new LengthAwarePaginator($articles, $total, 10);
    }
    return $articles;
}

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

function adIsOpened()
{

    if (!class_exists("App\AppConfig")) {
        return null;
    }

    $os     = request()->header('os', 'android');
    $config = \App\AppConfig::where([
        'group' => $os,
        'name'  => 'ad',
    ])->first();
    if ($config && $config->state === \App\AppConfig::STATUS_OFF) {
        return false;
    } else {
        return true;
    }
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

function seo_value($group, $name)
{

    if (!class_exists("App\Seo")) {
        return null;
    }

    return \App\Seo::getValue($group, $name);
}

function aso_value($group, $name)
{

    if (!class_exists("App\Aso")) {
        return null;
    }

    return \App\Aso::getValue($group, $name);
}

function getVodConfig(string $key)
{
    $appName = env('APP_NAME');
    $name    = sprintf('tencentvod.%s.%s', $appName, $key);
    return config($name);
}

function stopfunction($name)
{
    // \App\FunctionSwitch::close_function($name);
}

function getDownloadUrl()
{

    if (!class_exists("App\Aso")) {
        return null;
    }

    $apkUrl = \App\Aso::getValue('下载页', '安卓地址');
    if (is_null($apkUrl) || empty($apkUrl)) {
        return null;
    }
    return $apkUrl;
}

function is_local_env()
{
    return config('app.env') == 'local';
}

function is_dev_env()
{
    return config('app.env') == 'dev';
}

function douyinOpen()
{

    if (!class_exists("App\Config")) {
        return null;
    }

    $config = \App\Config::where([
        'name' => 'douyin',
    ])->first();
    if ($config && $config->value === \App\Config::CONFIG_OFF) {
        return false;
    } else {
        return true;
    }

}
