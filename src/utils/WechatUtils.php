<?php

namespace Haxibiao\Helpers\utils;

use App\Exceptions\UserException;
use App\OAuth;
use App\User;
use App\Wallet;
use GuzzleHttp\Client;
use haxibiao\helpers\utils\WechatMgUtils;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** inner class 1 --------------------- */

/**
 * error code 说明.
 * <ul>

 *    <li>-41001: encodingAesKey 非法</li>
 *    <li>-41003: aes 解密失败</li>
 *    <li>-41004: 解密后得到的buffer非法</li>
 *    <li>-41005: base64加密失败</li>
 *    <li>-41016: base64解密失败</li>
 * </ul>
 */
class ErrorCode
{
    public static $OK                = 0;
    public static $IllegalAesKey     = -41001;
    public static $IllegalIv         = -41002;
    public static $IllegalBuffer     = -41003;
    public static $DecodeBase64Error = -41004;
}

/** inner class 2 --------------------- */

/**
 * 对微信小程序用户加密数据的解密示例代码.
 *
 * @copyright Copyright (c) 1998-2014 Tencent Inc.
 */

class WXBizDataCrypt
{
    private $appid;
    private $sessionKey;

    /**
     * 构造函数
     * @param $sessionKey string 用户在小程序登录后获取的会话密钥
     * @param $appid string 小程序的appid
     */
    public function __construct($appid, $sessionKey)
    {
        $this->sessionKey = $sessionKey;
        $this->appid      = $appid;
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData($encryptedData, $iv, &$data)
    {
        if (strlen($this->sessionKey) != 24) {
            return ErrorCode::$IllegalAesKey;
        }
        $aesKey = base64_decode($this->sessionKey);

        if (strlen($iv) != 24) {
            return ErrorCode::$IllegalIv;
        }
        $aesIV = base64_decode($iv);

        $aesCipher = base64_decode($encryptedData);

        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj = json_decode($result);
        if ($dataObj == null) {
            return ErrorCode::$IllegalBuffer;
        }
        if ($dataObj->watermark->appid != $this->appid) {
            return ErrorCode::$IllegalBuffer;
        }
        $data = $result;
        return ErrorCode::$OK;
    }
}

//微信工具类
class WechatUtils
{
    protected static $instance = null;

    protected $client = null;
    protected $config = [];

    public function __construct()
    {
        $this->config = config('wechat');
        $this->client = new Client([
            'time_out' => 5,
        ]);
    }

    //单例
    public static function getInstance()
    {
        if (is_null(WechatUtils::$instance)) {
            WechatUtils::$instance = new WechatUtils();
        }
        return WechatUtils::$instance;
    }

    /**
     * 获取微信用户access_token
     *
     * @param [String] $code
     * @return Array
     */
    public function accessToken($code, $platform = 'dazhuan')
    {
        $accessTokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token';

        //默认使用答赚安卓版Wechat
        $appid  = Arr::get($this->config, 'wechat_app.appid');
        $secret = Arr::get($this->config, 'wechat_app.secret');
        //天天出题，使用ios版wechat
        if ($platform == "TTCT") {
            $appid  = Arr::get($this->config, 'tiantianchuti.appid');
            $secret = Arr::get($this->config, 'tiantianchuti.secret');
        }

        $response = $this->client->request('GET', $accessTokenUrl, [
            'query' => [
                'grant_type' => 'authorization_code',
                'code'       => $code,
                'appid'      => $appid,
                'secret'     => $secret,
            ],
        ]);

        $result = $response->getbody()->getContents();

        return empty($result) ? null : json_decode($result, true);
    }

    /**
     * 微信用户信息
     *
     * @param [String] $accessToken
     * @param [String] $openId
     * @return Array
     */
    public function userInfo($accessToken, $openId)
    {
        $userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo';

        $response = $this->client->request('GET', $userInfoUrl, [
            'query' => [
                'access_token' => $accessToken,
                'openid'       => $openId,
                'lang'         => 'zh_CN',
            ],
        ]);

        $result = $response->getbody()->getContents();

        return empty($result) ? null : json_decode($result, true);
    }

    public static function findWechatUser($unionId)
    {
        return OAuth::findWechatUser($unionId);
    }

    /**
     * 微信授权
     *
     * @param [String] $code
     * @return User
     */
    public static function auth($code, $platform)
    {
        $accessTokens = WechatUtils::getInstance()->accessToken($code, $platform);

        if (!is_array($accessTokens) || !array_key_exists('unionid', $accessTokens) || !array_key_exists('openid', $accessTokens)) {
            return failed_response('授权失败,参数错误');
        }

        //查询是否有该用户
        $user = OAuth::findWechatUser($accessTokens['unionid']);
        if (!is_null($user)) {

            if ($user->isDegregister()) {
                return failed_response('授权失败,参数错误');
            }

            return successful_response(['user' => [
                'id'        => $user->id,
                'api_token' => $user->api_token,
                'account'   => $user->account,
                'unionid'   => $accessTokens['unionid'],
                'openid'    => $accessTokens['openid'],
            ]],200);
        }

        return successful_response($accessTokens,200);
    }

    /**
     * 推送微信客服消息
     *
     * @param [String] $signature
     * @param [String] $timestamp
     * @param [String] $nonce
     * @param array $inputs
     * @return Array
     */
    public static function pushWechatMessage($signature, $timestamp, $nonce, array $inputs)
    {
        $wechatMgUtil = new WechatMgUtils;

        if ($wechatMgUtil->checkSignature($signature, $timestamp, $nonce)) {
            //这一步用于微信推送服务器消息验证
            if (isset($inputs['echostr'])) {
                //这里必须擦除缓冲区输出 不然会附带一个空格
                ob_end_clean();
                return $inputs['echostr'];
            }

            //用户进入客服事件
            if (isset($inputs['MsgType'])) {
                //发送者openid
                $userName = $inputs['FromUserName'];
                $link     = 'http://socket.datizhuanqian.com/wechat-redirect';
                $thumbUrl = 'http://cos.datizhuanqian.com/storage/app/logos/logo%40108x108.png';
                return $wechatMgUtil->sendLinkMessage($userName, "答题赚钱APP", "下载答题赚钱APP,进行提现吧!", $link, $thumbUrl);
            }
        }
    }

    /**
     * 绑定微信
     *
     * @param User $user
     * @param [String] $unionId
     * @param [String] $code
     * @return OAuth
     */
    public static function bindWechat(User $user, $unionId = null, $code = null, $version = 'v1')
    {
        //2.4.2之前版本用的微信登录接口
        if ($version == 'v1') {
            return self::oldBindWechat(...func_get_args());
        }

        throw_if(empty($code), UserException::class, '绑定失败,参数错误!');
        //获取微信token
        $accessTokens = WechatUtils::getInstance()->accessToken($code);
        throw_if(!Arr::has($accessTokens, ['unionid', 'openid']), UserException::class, '授权失败,请稍后再试!');

        $oauthModel = new OAuth;
        if (\method_exists($oauthModel, 'store')) {
            $oAuth = OAuth::store($user->id, 'wechat', $accessTokens['openid'], $accessTokens['unionid'], Arr::only($accessTokens, ['openid', 'refresh_token']));
            throw_if($oAuth->user_id != $user->id, UserException::class, '绑定失败,该微信已绑定其他账户!');
        } else {
            //建立oauth关联
            $oAuth = OAuth::firstOrNew([
                'oauth_type' => 'wechat',
                'oauth_id'   => $accessTokens['openid'],
            ], ['union_id' => $accessTokens['unionid']]);

            if (isset($oAuth->id)) {
                $oAuthData = $oAuth->data;
                throw_if(isset($oAuthData['openid']), UserException::class, '绑定失败,该微信已绑定其他账户!');
            }

            $oAuth->user_id = $user->id;
            $oAuth->data    = Arr::only($accessTokens, ['openid', 'refresh_token']);
            $oAuth->save();
        }

        //同步wallet OpenId
        $wallet          = Wallet::firstOrNew(['user_id' => $user->id]);
        $wallet->open_id = $accessTokens['openid'];
        $wallet->save();

        // $wechatUserInfo = WechatUtils::$instance->userInfo($accessTokens['access_token'], $accessTokens['openid']);
        // if ($wechatUserInfo && Str::contains($user->name, '匿名答友')) {
        //     $gender = null;
        //     if ($wechatUserInfo['sex'] == 1) {
        //         $gender = User::MALE_GENDER;
        //     } else if ($wechatUserInfo['sex'] == 2) {
        //         $gender = User::FEMALE_GENDER;
        //     }
        //     $user->name   = $wechatUserInfo['nickname'];
        //     $user->gender = $gender;
        //     $user->updateAvatar($wechatUserInfo['headimgurl']);
        //     $user->save();

        //     $wechatData  = array_merge($oAuth->data, $wechatUserInfo);
        //     $oAuth->data = $wechatData;
        //     $oAuth->save();
        // }

        return $oAuth;
    }

    public static function bindWechatWithCode(User $user, $code)
    {
        throw_if(empty($code), UserException::class, '绑定失败,参数错误!');
        //获取微信token
        $accessTokens = WechatUtils::getInstance()->accessToken($code);
        throw_if(!Arr::has($accessTokens, ['unionid', 'openid']), UserException::class, '授权失败,请稍后再试!');

        $oAuth = OAuth::store($user->id, 'wechat', $accessTokens['openid'], $accessTokens['unionid'], Arr::only($accessTokens, ['openid', 'refresh_token']));
        throw_if($oAuth->user_id != $user->id, UserException::class, '绑定失败,该微信已绑定其他账户!');

        //同步wallet OpenId
        $wallet          = Wallet::firstOrNew(['user_id' => $user->id]);
        $wallet->open_id = $accessTokens['openid'];
        $wallet->save();

        return $oAuth;
    }

    public static function bindWechatWithToken(User $user, $accessToken, $openId)
    {
        //获取微信token
        $accessTokens = WechatUtils::getInstance()->userInfo($accessToken, $openId);
        throw_if(!Arr::has($accessTokens, ['unionid', 'openid']), UserException::class, '授权失败,请稍后再试!');

        $oAuth = OAuth::store($user->id, 'wechat', $accessTokens['openid'], $accessTokens['unionid'], Arr::only($accessTokens, ['openid', 'refresh_token']));
        throw_if($oAuth->user_id != $user->id, UserException::class, '绑定失败,该微信已绑定其他账户!');

        //同步wallet OpenId
        $wallet          = Wallet::firstOrNew(['user_id' => $user->id]);
        $wallet->open_id = $accessTokens['openid'];
        $wallet->save();

        return $oAuth;
    }

    public static function oldBindWechat(User $user, $unionId = null, $code = null)
    {
        throw_if(is_null($unionId) && is_null($code), UserException::class, '绑定失败,参数错误!');

        $oAuth = OAuth::firstOrNew([
            'oauth_type' => 'wechat',
            'oauth_id'   => $unionId,
        ], ['union_id' => $accessTokens['unionid']]);

        throw_if(isset($oAuth->id), UserException::class, '绑定失败,该微信已绑定其他账户!');

        $oAuth->user_id = $user->id;
        $oAuth->save();

        return $oAuth;
    }

    public static function signInOAuth($code)
    {
        //获取微信token
        $accessTokens = WechatUtils::$instance->accessToken($code);
        throw_if(!Arr::has($accessTokens, ['unionid', 'openid']), UserException::class, '授权失败,请稍后再试!');
        //建立oauth关联
        $user = OAuth::findWechatUser($unionId);
        throw_if(!is_null($user) && method_exists($user, 'isDegregister') && $user->isDegregister(), UserException::class, '登录失败,该账户已注销!');

        return $oAuth;
    }

    /**
     * @param $wechatUserInfo
     * @param $user
     */
    public function syncWeChatInfo($wechatUserInfo, $user)
    {
        $user->name = $wechatUserInfo['nickname'];
        $headimgurl = $wechatUserInfo['headimgurl'];
        //将用户头像上传到服务器
        $stream = file_get_contents($headimgurl);
        $hash   = hash_file('md5', $headimgurl);
        $path   = 'images/' . $hash . '.jpg';
        Storage::cloud()->put($path, $stream);
        $user->avatar = Storage::cloud()->url($path);
        $user->save();
    }
}
