<?php

namespace Haxibiao\Helpers;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Exception;


class AlipayUtils
{
    protected $factory;
    protected $config;
    private static $instance;

    public static function config(array $config)
    {
        AlipayUtils::checkConfigMustParams($config);

        $options              = new Config();
        $options->protocol    = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType    = 'RSA2';

        foreach ($config as $attr => $value) {
            $options->$attr = $value;
        }
        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        // $options->alipayPublicKey = '';

        //可设置异步通知接收服务地址（可选）
        // $options->notifyUrl = "<-- 请填写您的支付类接口异步通知接收服务地址，例如：https://www.test.com/callback -->";

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        // $options->encryptKey = "<-- 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA== -->";
        //检测类是否被实例化
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }

        self::$instance->config  = $options;
        self::$instance->factory = Factory::setOptions($options);

        return self::$instance;
    }

    public static function checkConfigMustParams(array $params)
    {
        $mustParams = ['appId', 'merchantPrivateKey', 'alipayCertPath', 'alipayCertPath', 'alipayRootCertPath', 'merchantCertPath'];
        foreach ($mustParams as $param) {
            throw_if(!isset($params[$param]), \Exception::class, $param . ' 是必传参数!');
        }
    }

    public function userInfo($code)
    {
        $accessTokens = $this->getAccessTokens($code);
        throw_if(!isset($accessTokens['access_token']), \Exception::class, 'accessToken 不存在!');
        $accessToken = $accessTokens['access_token'];
        $rsp         = Factory::util()->generic()->execute('alipay.user.info.share', ['auth_token' => $accessToken], []);
        $body        = json_decode($rsp->httpBody, true);
        $result      = isset($body['alipay_user_info_share_response']) ? $body['alipay_user_info_share_response'] : $body;

        return array_merge($result, $accessTokens);
    }

    public function getAccessTokens($code)
    {
        $rsp  = Factory::base()->oauth()->getToken($code);
        $body = json_decode($rsp->httpBody, true);

        return isset($body['alipay_system_oauth_token_response']) ? $body['alipay_system_oauth_token_response'] : $body;
    }
}
