<?php
namespace haxibiao\helpers;

class AdUtils
{

    //主要的接口返回广告信息配置等.....
    public static function getAdInfo()
    {
        $ad       = self::get_cpc_ad();
        $bannerAd = self::getBannerAd();
        $videoAd  = self::getVideoAd();

        //答题赚钱2.0 banner广告需要返回不同code id
        if (get_appid() == 'com.datizhuanqian' && getAppVersion() >= '2.0.0' && is_prod_env()) {
            $bannerAd['codeid'] = '916518401';
        }

        $adInfo = [
            'tt_appid'          => self::get_tt_appid(),
            'tt_codeid'         => self::get_tt_codeid(),
            'cpc_ad_id'         => $ad ? $ad->id : 0,
            'cpc_ad_url'        => $ad ? $ad->redirect_url : null,
            'bannerAd'          => $bannerAd,
            'fullScreenVideoAd' => $videoAd,
        ];

        return $adInfo;
    }

    public static function get_tt_appid()
    {
        //ios 的上架后更新id,只有答妹ios
        if (getAppOS() == "ios") {
            return "5017553";
        }

        //目前只有com.dianmoge 1.6版本开始传入这个header
        if (getAppId() == "com.dianmoge") {
            return "5017576";
        }

        if (getAppId() == "com.damei") {
            return "5026208";
        }

        // if (!in_array(getAppStore(), ['oppo', 'xiaomi', 'huawei'])) {
        //     return "5016582"; //TODO:测试appid, 临时对未上架成功的com.damei的包能看测试视频
        // }

        return isAndroidApp() ? env('TT_APPID') : "5016582";
    }

    public static function get_tt_codeid()
    {
        //ios 的上架后更新id,只有答妹ios
        if (getAppOS() == "ios") {
            return "917553284";
        }

        //目前只有com.dianmoge 1.6版本开始传入这个header
        if (getAppId() == "com.dianmoge") {
            return "917576640";
        }

        if (getAppId() == "com.damei") {
            return "926208759";
        }

        // if (!in_array(getAppStore(), ['oppo', 'xiaomi', 'huawei'])) {
        //     return "916582412";
        // }

        return isAndroidApp() ? env('TT_CODEID') : "916582412";
    }

    public static function get_cpc_ad()
    {
        //随机找个商户的随机的CPC广告链接
        $merchants = \App\Merchant::normal()->get();
        if ($merchants->count()) {
            $merchant = $merchants->random();
            $banners  = $merchant->banners;
            if ($banners->count()) {
                $banner = $banners->random();
                return $banner;
            }
        }

        //没有ad 前端可以关闭
        return null;
    }

    public static function getBannerAd()
    {
        $prodEnv  = is_prod_env();
        $adConfig = config('ad');
        $package  = getAppId();

        if ($package == "com.dianmoge") {
            $appid  = 'dianmoge.banner.appid';
            $codeid = 'dianmoge.banner.codeid';
        } else {
            $app = str_replace('com.', '', $package);
            if (empty($app)) {
                $app = 'datizhuanqian';
            }
            $appid  = $app . '.banner.appid.' . ($prodEnv ? 'prod' : 'staging');
            $codeid = $app . '.banner.codeid.' . ($prodEnv ? 'prod' : 'staging');
        }

        return [
            'appid'  => array_get($adConfig, $appid),
            'codeid' => array_get($adConfig, $codeid),
        ];
    }

    public static function getVideoAd()
    {
        $prodEnv  = is_prod_env();
        $adConfig = config('ad');
        $package  = getAppId();

        if ($package == "com.dianmoge") {
            $appid  = 'dianmoge.fullScreenVideo.appid';
            $codeid = 'dianmoge.fullScreenVideo.codeid';
        } else {
            $app = str_replace('com.', '', $package);
            if (empty($app)) {
                $app = 'datizhuanqian';
            }
            $appid  = $app . '.fullScreenVideo.appid.' . ($prodEnv ? 'prod' : 'staging');
            $codeid = $app . '.fullScreenVideo.codeid.' . ($prodEnv ? 'prod' : 'staging');
        }

        return [
            'appid'  => array_get($adConfig, $appid),
            'codeid' => array_get($adConfig, $codeid),
        ];
    }
}
