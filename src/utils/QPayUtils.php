<?php

namespace App\Utils;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

class QPayUtils
{
    protected $client = null;
    protected $config = [];

    /**
     * 相关接口文档日志:https://qpay.qq.com/buss/wiki/206/1215
     */
    public function __construct()
    {
        $this->config = config('pay.qq');
        $this->client = new Client(['time_out' => 5]);
    }

    // public function userInfo($accessToken, $openID)
    // {
    //     $userInfoUrl = 'https://graph.qq.com/user/get_user_info';
    //     $response    = $this->client->request('GET', $userInfoUrl, [
    //         'query' => [
    //             'access_token'       => $accessToken,
    //             'openid'             => $openID,
    //             'oauth_consumer_key' => $this->config['appid'],
    //         ],
    //     ]);
    //     $result = $response->getbody()->getContents();

    //     return empty($result) ? null : json_decode($result, true);
    // }

    public function transfer(string $outBizNo, string $payId, $amount, $remark = null)
    {
        $url                    = "https://api.qpay.qq.com/cgi-bin/epay/qpay_epay_b2c.cgi";
        $params                 = Arr::except($this->config, 'api_key');
        $params['nonce_str']    = Str::random(32);
        $params['out_trade_no'] = $outBizNo;
        $params['uin']          = $payId;
        $params['total_fee']    = $amount;
        $params['memo']         = $remark;
        $params['sign']         = $this->generateSign($params, $this->config('api_key'));

        //数组转XML
        $XML      = $this->arrayToXml($parameter);
        $response = $this->requestUrl($XML, $url);
        //XML转数组
        $result = $this->xmlToArray($response);

        return empty($result) ? null : $result;
    }

    /**
     * 通过CURL请求去请求QQ转账
     */
    private function requestUrl($XML, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $XML);
        //设置该属性，在执行curl_exec后返回的才是xml数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:text/xml; charset=utf-8"));

        //证书路径
        //pem
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, base_path('cert/qq/apiclient_cert.pem'));
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, base_path('cert/qq/apiclient_key.pem'));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * 根据请求参数计算签名
     */
    private function generateSign($parameter, $apiKey)
    {
        //通过Key排序
        ksort($parameter);
        $signTemp = "";

        //通过ASCII排序将请求参数拼接
        foreach ($parameter as $key => $value) {
            $signTemp = $signTemp . $key . '=' . $value . '&';
        }

        //拼接APIKey
        $signTemp = $signTemp . 'key' . '=' . $apiKey;

        //计算签名
        $sign = strtoupper(md5($signTemp));

        return $sign;
    }

    /**
     * 数组转XML
     */
    private function arrayToXml($arr)
    {
        if (!is_array($arr) || count($arr) == 0) {
            return '';
        }

        $xml = "<xml>";
        foreach ($arr as $key => $val) {

            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * XML转数组
     */
    private function xmlToArray($XML)
    {
        return json_decode(json_encode(simplexml_load_string($XML, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}
