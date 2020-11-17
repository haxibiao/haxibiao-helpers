<?php

namespace Haxibiao\Helpers\utils;


use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

//手机号工具类
class PhoneUtils
{
    protected static $instance = null;

    protected $client = null;
    public function __construct()
    {
        $this->client = new Client();
    }

    //单例
    public static function getInstance()
    {
        if (is_null(PhoneUtils::$instance)) {
            PhoneUtils::$instance = new PhoneUtils();
        }
        return PhoneUtils::$instance;
    }

    /**
     * 获取用户手机号
     *
     * @param [String] $code
     * @return Array
     */
    public function accessToken($code)
    {
        $accessTokenUrl = 'https://www.cmpassport.com/unisdk/rsapi/loginTokenValidate';

        //构造请求参数
        $params=self::buildParams($code);

        //发送手机号解析请求
        $response = $this->client->request('POST', $accessTokenUrl, [
            'json'=>$params
        ]);

        $result = $response->getbody()->getContents();
//        dd($result);
        return empty($result) ? null : json_decode($result, true);
    }

    public function buildParams($code){
        //所有参数类型均为string
        $version='2.0';
        //$msgid标识请求的随机数即可(1-36位
        $msgid=strval(str_random(20));
        //请求消息发送的系统时间，精确到毫秒，共17位，
        $systemtime=strval(self::msectime(7));
        //ip强校验
        $strictcheck='0';
        //appid 业务在统一认证申请的应用id
        $appid = config('phone.app_id');
        //token 需要解析的凭证值
        $token=strval($code);
        //appsecret 验签码
        $appsecret = config('phone.app_secret');
        //sign 签名 MD5(appid +version + msgid + systemtime + strictcheck + token +APPSecret)
        $sign = md5($appid . $version . $msgid.$systemtime.$strictcheck.$token.$appsecret);

        $params=[
            'version'=>$version,
            'msgid'=>$msgid,
            'systemtime'=>$systemtime,
            'strictcheck'=>$strictcheck,
            'appid'=>$appid,
            'token'=>$token,
            'sign'=>$sign,
        ];
        Log::info("移动获取号码接口参数",$params);
        return $params;
    }

    /**
     * 取毫秒级时间戳，默认返回普通秒级时间戳 time() 及 3 位长度毫秒字符串
     *
     * @param int  $msec_length 毫秒长度，默认 3
     * @param int  $random_length 添加随机数长度，默认 0
     * @param bool $dot 随机是否存入小数点，默认 false
     * @param int  $delay 是否延迟，传入延迟秒数，默认 0
     * @return string
     */
    public static function  msectime($msec_length = 3, $random_length = 0, $dot = false, $delay = 0) {
        list($msec, $sec) = explode(' ', microtime());
        $rand = $random_length > 0 ?
            number_format(
                mt_rand(1, (int)str_repeat('9', $random_length))
                * (float)('0.' . str_repeat('0', $random_length - 1) . '1'),
                $random_length,
                '.',
                '') : '';
        $msectime = sprintf('%.0f', (floatval($msec) + floatval($sec) + $delay) * pow(10, $msec_length));
        return $dot ? $msectime . '.' . substr($rand, 2) : $msectime . substr($rand, 2);
    }
}
