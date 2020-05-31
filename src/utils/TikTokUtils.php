<?php
namespace haxibiao\helpers;

use App\Exceptions\UserException;
use App\OAuth;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

/**
 * 抖音开放平台工具类
 */
class TikTokUtils
{
    protected static $instance = null;
    protected $client          = null;
    protected $config          = [];
    const BASE_URL             = 'https://open.douyin.com';

    public function __construct()
    {
        $this->config = config('tiktok');
        $this->client = new Client([
            'time_out' => $this->config['time_out'],
            'base_uri' => self::BASE_URL,
        ]);
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new TikTokUtils;
        }
    }

    /**
     * 获取抖音用户access_token
     *
     * @param [String] $code
     * @return Array
     */
    public function accessToken($code)
    {
        $response = $this->client->request('GET', '/oauth/access_token/', [
            'query' => [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_key'    => Arr::get($this->config, 'client_key'),
                'client_secret' => Arr::get($this->config, 'client_secret'),
            ],
        ]);

        $result = $response->getbody()->getContents();

        return empty($result) ? null : json_decode($result, true);
    }

    /**
     * 抖音用户信息
     *
     * @param [String] $accessToken
     * @param [String] $openId
     * @return Array
     */
    public function userInfo($accessToken, $openId)
    {
        $response = $this->client->request('GET', '/oauth/userinfo/', [
            'query' => [
                'access_token' => $accessToken,
                'open_id'      => $openId,
            ],
        ]);

        $result = $response->getbody()->getContents();

        return empty($result) ? null : json_decode($result, true);
    }

    /**
     * 抖音视频上传
     *
     * @param [String] $accessToken
     * @param [String] $openId
     * @return Array
     */
    public function videoUpload($accessToken, $openId, $videoPath)
    {
        $response = $this->client->request('POST', '/video/upload/', [
            'query'     => [
                'access_token' => $accessToken,
                'open_id'      => $openId,
            ],
            'multipart' => [
                [
                    'name'     => 'video',
                    'contents' => file_get_contents($videoPath),
                ],
            ],
        ]);

        $result = $response->getbody()->getContents();

        return empty($result) ? null : json_decode($result, true);
    }

    /**
     * 创建抖音视频
     *
     * @param [String] $accessToken
     * @param [String] $openId
     * @param [String] $videoId
     * @return Array
     */
    public function createVideo($accessToken, $openId, $videoId)
    {
        //TODO: 这个文本得传进来吧
        $text     = '无聊分享到抖音';
        $response = $this->client->request('POST', '/video/create/', [
            'query'       => [
                'access_token' => $accessToken,
                'open_id'      => $openId,
            ],
            'form_params' => [
                'video_id' => $videoId,
                'text'     => $text,
            ],
        ]);

        $result = $response->getbody()->getContents();

        return empty($result) ? null : json_decode($result, true);
    }

    //绑定抖音
    public static function bindTikTok($user, $code)
    {
        throw_if(empty($code), UserException::class, '绑定失败,参数错误!');

        $accessTokens = self::getInstance()->accessToken($code);
        $openId       = Arr::get($accessTokens, 'open_id');
        throw_if(empty($openId), UserException::class, '授权失败,请稍后再试!');

        $oauth = OAuth::firstOrNew(['oauth_type' => 'tiktok', 'oauth_id' => $openId]);

        throw_if(isset($oauth->id), UserException::class, '该抖音已被绑定,请尝试其他账户!');

        $userInfo = self::getInstance()->userInfo($accessTokens['access_token'], $openId);

        $oauth->user_id = $user->id;
        $oauth->data    = $userInfo;
        $oauth->save();

        return $oauth;
        //同步wallet OpenId

        //同步昵称、头像、性别...
    }
}
