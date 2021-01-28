<?php

namespace Haxibiao\Helpers\utils;

use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Breeze\OAuth;
use Haxibiao\Breeze\User;
use Haxibiao\Wallet\Wallet;
use Haxibiao\Wallet\Withdraw;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * 第三方平台绑定，授权工具类
 */
class OAuthUtils
{
    public static function signIn($user, $code, $type)
    {
        $oauth = self::getUserOauth($user, $type);
        throw_if(is_null($oauth), UserException::class, '授权失败,该账户未绑定!');

        if ($type == 'wechat') {
            return WeChatUtils::signInOAuth($code);
        }
    }

    public static function bind($user, $code, $type)
    {
        // $brand = OAuth::typeTranslator($type);
        throw_if(self::getUserOauth($user, $type), UserException::class, '您已绑定成功,请直接登录!');
        throw_if(!method_exists(self::class, $type), UserException::class, '绑定失败,该授权方式不存在!');

        $oauth = self::$type($user, $code);
        return $oauth;
    }

    public static function wechat(User $user, $code)
    {
        return WeChatUtils::bindWechat($user, null, $code, 'v2');
    }

    public static function alipay(User $user, $code)
    {
        return self::bindAlipay($user, $code);
    }

    public static function tiktok(User $user, $code)
    {
        return TikTokUtils::bindTikTok($user, $code);
    }

    public static function getUserOauth(User $user, $oAuthType)
    {
        return OAuth::where(['oauth_type' => $oAuthType, 'user_id' => $user->id])->first();
    }

    public static function bindAlipay($user, $code)
    {
        throw_if(empty($code), UserException::class, '绑定失败,参数错误!');
        $userInfo = self::userInfo($code);
        $openId   = Arr::get($userInfo, 'user_id');
        throw_if(empty($openId), UserException::class, $userInfo['errorMsg'] ?? '授权失败,请稍后再试!');

        $oauth = OAuth::firstOrNew(['oauth_type' => 'alipay', 'oauth_id' => $openId]);

        throw_if(isset($oauth->id), UserException::class, '该支付宝已被绑定,请尝试其他账户!');

        //更新OAuth绑定
        $oauth->user_id = $user->id;
        $oauth->data    = $userInfo;
        $oauth->save();

        //更新钱包OPENID
        $wallet = Wallet::firstOrNew(['user_id' => $user->id]);
        $wallet->setPayId($openId, Withdraw::ALIPAY_PLATFORM);
        $wallet->save();

        return $oauth;

        //同步昵称、头像、性别...
    }

    /**
     * @param $code alipay sdk授权码
     */
    public static function userInfo($code)
    {
        $userInfo          = [];
        $_GET['auth_code'] = $code;
        $config            = [
            'appId'              => env('ALIPAY_AUTH_APP_ID', '2019112969489742'),
            'merchantPrivateKey' => file_get_contents(base_path('cert/alipay/auth/private_key')),
            'alipayCertPath'     => base_path('cert/alipay/auth/alipayCertPublicKey_RSA2.crt'),
            'alipayRootCertPath' => base_path('cert/alipay/auth/alipayRootCert.crt'),
            'merchantCertPath'   => base_path('cert/alipay/auth/appCertPublicKey.crt'),
        ];
        try {
            error_reporting(E_ALL ^ E_DEPRECATED);
            $alipayUtils = AlipayUtils::config($config);
            $userInfo    = $alipayUtils->userInfo($code);
        } catch (\Exception $ex) {
            $userInfo['errorMsg'] = $ex->getMessage();
        }

        return $userInfo;
    }

    public static function isAlipayOpenId($openId)
    {
        return Str::startsWith($openId, '2088') && !is_email($openId);
    }
}
