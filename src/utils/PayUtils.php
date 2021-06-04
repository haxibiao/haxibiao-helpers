<?php

namespace Haxibiao\Helpers\utils;

use Exception;
use Illuminate\Support\Str;
use Yansongda\Pay\Pay;

/**
 * 目前支付主要在用的代码
 */
class PayUtils
{
    const PLATFORMS = [
        'alipay',
        'wechat',
        'qq',
    ];

    private $platform;

    private $instance;

    const WITHDRAW_SERVER_IP = '203.195.161.189';

    public function __construct($platform)
    {
        if (!in_array($platform, self::PLATFORMS)) {
            throw new Exception('支付方式不存在!');
        }

        $this->platform = $platform;
        if ($platform != 'qq') {
            $this->instance = Pay::$platform(config('pay.' . $platform));
        } else {
            $this->instance = new QPayUtils;
        }

    }

    public function transfer(string $outBizNo, string $payId, $realName, $amount, $remark = null)
    {
        $order = [];
        if ($this->platform == 'wechat') {
            //微信平台 amount 单位:/分
            $amount *= 100;
            $order = [
                'partner_trade_no' => $outBizNo,
                'openid'           => $payId,
                'check_name'       => 'NO_CHECK',
                're_user_name'     => $realName,
                'amount'           => $amount,
                'desc'             => $remark,
                'type'             => 'app',
                'spbill_create_ip' => self::WITHDRAW_SERVER_IP,
            ];
        } else if ($this->platform == 'alipay') {
            $order = [
                'out_biz_no'   => $outBizNo,
                'biz_scene'    => 'DIRECT_TRANSFER',
                'trans_amount' => $amount,
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'payee_info'   => [
                    'identity'      => $payId,
                    'identity_type' => OAuthUtils::isAlipayOpenId($payId) ? 'ALIPAY_USER_ID' : 'ALIPAY_LOGON_ID',
                    'name'          => $realName,
                ],
                'remark'       => $remark,
                'order_title'  => $remark,
            ];
        } else if ($this->platform == 'qq') {
            //QQ钱包 amount 单位:/分
            $amount *= 100;
            $order = [
                'outBizNo'  => $outBizNo,
                'openid'    => $payId,
                'total_fee' => $amount,
                'memo'      => $remark,
            ];
        }

        return $this->instance->transfer($order);
    }

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

    public static function isAlipayOpenId($payId)
    {
        return Str::startsWith($payId, '2088') && !is_email($payId);
    }
}
