<?php

namespace haxibiao\helper;

use Exception;
use haxibiao\helper\OAuthUtils;
use Yansongda\Pay\Pay;

/**
 * 目前支付主要在用的代码
 */
class PayUtils
{
    const PLATFORMS = [
        'alipay',
        'wechat',
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
        $this->instance = Pay::$platform(config('pay.' . $platform));
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
        }

        return $this->instance->transfer($order);
    }

}
