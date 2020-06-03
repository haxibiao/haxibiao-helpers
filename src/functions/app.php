<?php

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

/*****************************
 * *****答赚web网页兼容*********
 * ***************************
 */
//是否处于备案状态
function isRecording()
{
    if (class_exists("App\\AppConfig", true)) {

        $config = \App\AppConfig::where([
            'group' => 'record',
            'name'  => 'web',
        ])->first();
        if ($config === null) {
            return true;
        }
        if ($config->state === \App\AppConfig::STATUS_ON) {
            return true;
        }

        return false;
    }
}

function qrcode_url()
{
    if (class_exists("App\\Aso", true)) {
        $apkUrl = \App\Aso::getValue('下载页', '安卓地址');
        $logo   = small_logo();
        $qrcode = SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(250)->encoding('UTF-8');
        if (str_contains($logo, env('COS_DOMAIN'))) {
            $qrcode->merge($logo, .1, true);
        } else {
            if (file_exists(public_path($logo))) {
                $qrcode->merge(public_path($logo), .1, true);
            }
        }

        $qrcode = $qrcode->generate($apkUrl);

        $path = base64_encode($qrcode);

        return $path;
    }
}

function small_logo()
{
    if (class_exists("App\\Aso", true)) {
        $logo = \App\Aso::getValue('下载页', 'logo');

        if (empty($logo)) {
            return '/logo/' . env('APP_DOMAIN') . '.small.png';
        } else {
            return $logo;
        }
    }
}
