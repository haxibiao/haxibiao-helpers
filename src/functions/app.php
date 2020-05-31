<?php
//FIXME: 重构 haxibiao-config

use SimpleSoftwareIO\QrCode\Facades\QrCode;

function small_logo()
{
    if (!class_exists("App\Aso")) {
        return null;
    }

    if (project_is_dtzq()) {
        return '/picture/logo.png';
    }

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
    if (!class_exists("App\Aso")) {
        return null;
    }

    if (project_is_dtzq()) {
        $apkUrl = "http://datizhuanqian.com/download"; //TODO: env?
    } else {
        $apkUrl = \App\Aso::getValue('下载页', '安卓地址');
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

//FIXME: 这个依赖的只有 haxibiao-media
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
