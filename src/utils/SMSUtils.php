<?php

namespace Haxibiao\Helpers\utils;

use App\Exceptions\UserException;
use App\Mail\SendNotificationMail;
use App\User;
use App\VerificationCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Overtrue\EasySms\EasySms;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Sms\V20190711\SmsClient;

/**
 * SMS相关工具类（基于EasySms）
 */
class SMSUtils
{
    protected static $instance = null;
    protected static $verificationCode;

    //单例
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new SMSUtils();
        }
    }

    public static function getVerifyLog($account)
    {
        return VerificationCode::where('account', $account)
            ->byValid(VerificationCode::CODE_VALID_TIME)
            ->orderBy('id', 'desc')
            ->first();
    }

    public static function sendVerificationCode($account, String $code = null, $action): VerificationCode
    {
        $code  = empty($code) ? random_str(4) : $code;
        $field = account($account);
        throw_if(!in_array($field, ['phone', 'email']), UserException::class, '账号格式错误!');

        //绑定微信必须要账号存在
        if ($action == VerificationCode::WECHAT_BIND) {
            $userExisted = User::where('account', $account)->exists();
            throw_if($userExisted, UserException::class, '账号已存在,请登录后绑定微信!');
        }

        $channel                = $field == 'phone' ? VerificationCode::SMS_CHANNEL : VerificationCode::EMAIL_CHANNEL;
        $user                   = User::where('account', $account)->first();
        self::$verificationCode = VerificationCode::create([
            'user_id' => $user->id ?? null,
            'account' => $account,
            'channel' => $channel,
            'code'    => $code,
            'action'  => $action,
        ]);

        //FIXME:貌似没有地方用到这个actu
        // if (!in_array(env("APP_NAME"), ["datizhuanqian", "damei"])) {
        //     self::$verificationCode->update(["actu" => "default"]);
        // }
        //拼装数据
        $data = [
            'account' => $account,
            'code'    => $code,
            'name'    => $user->name ?? $account,
            'action'  => VerificationCode::getVerificationActions()[$action],
        ];
        try {
            $senderResult = $field == 'phone' ? self::sendSMS($data) : self::sendMail($data);
        } catch (\Exception$ex) {
            Log::error($ex);
            self::$verificationCode->delete();
            throw new UserException('验证码发送失败,请稍后再试！');
        }

        return self::$verificationCode;
    }

    /**
     * @Author      XXM
     * @DateTime    2019-03-06
     * @description [发送短信]
     * @param       array         $data [description]
     * @return      [type]              [description]
     */
    public static function sendSMS(array $data)
    {
        //手机短信通知
        SMSUtils::sendVerifyCode($data['account'], $data['action']['sms'], $data['code']);
        return 1;
    }

    /**
     * @Author      XXM
     * @DateTime    2019-03-06
     * @description [description]
     * @param       array         $data [description]
     * @return      [type]              [description]
     */
    public static function sendMail(array $data)
    {
        $data['action'] = $data['action']['mail'];
        $mail           = (new SendNotificationMail($data))->onQueue('emails');

        // 发送邮件到队列处理
        Mail::to($data['account'])->queue($mail);
        return 1;
    }

    /**
     * @param $mobile 手机号码
     * @param null $template 短信模板
     * @return int
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     * @throws \Overtrue\EasySms\Exceptions\NoGatewayAvailableException
     */
    public static function sendVerifyCode($mobile, $action, $code = null)
    {
        $easySms = new EasySms(config('sms'));
        $easySms->send($mobile, [
            'template' => self::getTemplate($action),
            'data'     => [
                'code' => $code,
            ],
        ]);
    }

    public static function templates()
    {
        return [
            'RESET_PASSWORD'         => [
                'aliyun' => 'SMS_157655209',
                'qcloud' => '362867',
            ],
            'USER_REGISTER'          => [
                'aliyun' => 'SMS_157655210',
                'qcloud' => '358497',
            ],
            'USER_INFO_CHANGE'       => [
                'aliyun' => 'SMS_157655208',
                'qcloud' => '358492',
            ],
            'USER_LOGIN'             => [
                'aliyun' => 'SMS_157655212',
                'qcloud' => '358496',
            ],
            'WECHAT_BIND'            => [
                'aliyun' => null,
                'qcloud' => '358496',
            ],
            'EXCHANGE_REMIND'        => [
                'aliyun' => null,
                'qcloud' => '501361',
            ],
            'BIND_PHONE'             => [
                'aliyun' => null,
                'qcloud' => '668141',
            ],
            'EXCEPTION_LOG'          => [
                'aliyun' => null,
                'qcloud' => '915190',
            ],
            'NOVA_NEW_USER_WITHDRAW' => [
                'aliyun' => null,
                'qcloud' => '606303',
            ],
        ];
    }

    /**
     * 发送统计短信给内部人员
     */
    public static function sendNovaMessage($mobile, $action, array $data)
    {
        $easySms = new EasySms(config('sms'));
        $easySms->send($mobile, [
            'template' => self::getTemplate($action),
            'data'     => $data,
        ]);
        return true;
    }

    public static function getTemplate($action)
    {
        $gateways = config('sms.default.gateways');
        if (empty($gateways)) {
            throw new \App\Exceptions\UserException('短信发送失败,请联系官方人员！');
        }
        $gateways  = reset($gateways);
        $templates = self::templates();
        return $templates[$action][$gateways];
    }

    /**
     * 安保联盟 发送短信验证码
     *
     * @param $mobile
     * @param $action
     * @param null $code
     * @return null
     */
    public static function ablmSendVerifyCode($mobile, $action, $code = null)
    {

        $cred = new Credential(config('vod.secret_id'), config('vod.secret_key'));

        $httpProfile = new HttpProfile();
        $httpProfile->setReqMethod("GET"); // POST 请求（默认为 POST 请求）
        $httpProfile->setReqTimeout(30); // 请求超时时间，单位为秒（默认60秒）
        $httpProfile->setEndpoint("sms.tencentcloudapi.com"); // 指定接入地域域名（默认就近接入）

        // 实例化一个 client 选项，可选，无特殊需求时可以跳过
        $clientProfile = new ClientProfile();
        $clientProfile->setSignMethod("TC3-HMAC-SHA256"); // 指定签名算法（默认为 HmacSHA256）
        $clientProfile->setHttpProfile($httpProfile);

        $client = new SmsClient($cred, "ap-guangzhou", $clientProfile);

        // 实例化一个 sms 发送短信请求对象，每个接口都会对应一个 request 对象。
        $req = new SendSmsRequest();

        /* 填充请求参数，这里 request 对象的成员变量即对应接口的入参
         * 您可以通过官网接口文档或跳转到 request 对象的定义处查看请求参数的定义
         * 基本类型的设置:
         * 帮助链接：
         * 短信控制台：https://console.cloud.tencent.com/smsv2
         * sms helper：https://cloud.tencent.com/document/product/382/3773 */

        /* 短信应用 ID: 在 [短信控制台] 添加应用后生成的实际 SDKAppID，例如1400006666 */
        $req->SmsSdkAppid = "1400395100";

        /* 短信签名内容: 使用 UTF-8 编码，必须填写已审核通过的签名，可登录 [短信控制台] 查看签名信息 */
        $req->Sign = "安保联盟";

        /* 下发手机号码，采用 e.164 标准，+[国家或地区码][手机号]
         * 例如+8613711112222， 其中前面有一个+号 ，86为国家码，13711112222为手机号，最多不要超过200个手机号*/
        $req->PhoneNumberSet = array("+86" . $mobile);

        /* 模板 ID: 必须填写已审核通过的模板 ID。可登录 [短信控制台] 查看模板 ID */
        $req->TemplateID = "655788";

        /* 模板参数: 若无模板参数，则设置为空*/
        $req->TemplateParamSet = array($code, "3");

        // 通过 client 对象调用 SendSms 方法发起请求。注意请求方法名与请求对象是对应的
        $client->SendSms($req);

        return $code;
    }
}
