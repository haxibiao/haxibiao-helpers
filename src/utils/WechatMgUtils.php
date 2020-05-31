<?php
namespace haxibiao\helpers;

use GuzzleHttp\Client;
use haxibiao\helper\WXBizDataCrypt;
use Illuminate\Support\Arr;

//微信小程序用
class WechatMgUtils
{
    protected $client = null;
    protected $config = [];

    public function __construct()
    {
        $this->config = config('wechat');
        $this->client = new Client([
            'time_out' => $this->config['time_out'],
        ]);
    }

    public function checkSignature($signature, $timestamp, $nonce)
    {
        $token = 'hxbwechat';
        //1.将排序后的三个参数拼接后用sha1加密
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        //2. 将加密后的字符串与 signature 进行对比, 判断该请求是否来自微信
        return $tmpStr == $signature;
    }

    public function userSession($code, $appid, $secret)
    {
        $codeSessionUrl = 'https://api.weixin.qq.com/sns/jscode2session';
        $response       = $this->client->request('GET', $codeSessionUrl, [
            'query' => [
                'grant_type' => 'authorization_code',
                'js_code'    => $code,
                'appid'      => $appid,
                'secret'     => $secret,
            ],
        ]);

        $result = $response->getbody()->getContents();

        return is_null($result) ? null : json_decode($result, true);
    }

    public function decodePhone($appid, $sessionKey, $encryptedData, $iv)
    {
        $dataCrypt = new WXBizDataCrypt($appid, $sessionKey);
        $errCode   = $dataCrypt->decryptData($encryptedData, $iv, $data);

        return $errCode == 0 ? json_decode($data, true) : $errCode;
    }

    public function sendLinkMessage($userName, $title, $description, $url, $thumb_url = null)
    {
        $body = json_encode([
            'touser'  => $userName,
            'msgtype' => 'link',
            'link'    => [
                "title"       => $title,
                "description" => $description,
                "url"         => $url,
                "thumb_url"   => $thumb_url,
            ]], JSON_UNESCAPED_UNICODE);
        return $this->sendMessage($userName, $body);
    }

    protected function sendMessage($userName, $body)
    {
        $accessToken = $this->accessToken($this->config['wechat_mg']['appid'], $this->config['wechat_mg']['secret']);
        $url         = sprintf('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=%s', $accessToken);

        $response = $this->client->request('POST', $url, ['body' => $body]);
        $result   = $response->getbody()->getContents();

        return is_null($result) ? null : json_decode($result, true);
    }

    public function accessToken($appid, $secret)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token';

        $response = $this->client->request('GET', $url, [
            'query' => [
                'grant_type' => 'client_credential',
                'appid'      => $appid,
                'secret'     => $secret,
            ],
        ]);

        $result = $response->getbody()->getContents();

        if (!is_null($result)) {
            $result = json_decode($result, true);
        }

        return Arr::get($result, 'access_token');
    }
}
