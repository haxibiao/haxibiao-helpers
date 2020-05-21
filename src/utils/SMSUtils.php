<?php

namespace App\Helpers;

use App\Exceptions\UserException;
use App\Mail\SendNotificationMail;
use App\User;
use App\VerificationCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Overtrue\EasySms\EasySms;

/**
 * SMS相关工具类（基于EasySms）
 */
class SMSUtils
{
    protected static $instance = null;
    protected $verificationCode;

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

    public static function sendVerificationCode($account, $code = null, $action): VerificationCode
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

        //拼装数据
        $data = [
            'account' => $account,
            'code'    => $code,
            'name'    => $user->name ?? $account,
            'action'  => VerificationCode::getVerificationActions()[$action],
        ];
        try {
            $senderResult = $field == 'phone' ? self::sendSMS($data) : self::sendMail($data);
        } catch (\Exception $ex) {
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
        return $code;
    }

    public static function templates()
    {
        return [
            'RESET_PASSWORD'   => [
                'aliyun' => 'SMS_157655209',
                'qcloud' => '362867',
            ],
            'USER_REGISTER'    => [
                'aliyun' => 'SMS_157655210',
                'qcloud' => '358497',
            ],
            'USER_INFO_CHANGE' => [
                'aliyun' => 'SMS_157655208',
                'qcloud' => '358492',
            ],
            'USER_LOGIN'       => [
                'aliyun' => 'SMS_157655212',
                'qcloud' => '358496',
            ],
            'WECHAT_BIND'      => [
                'aliyun' => null,
                'qcloud' => '358496',
            ],
            'EXCHANGE_REMIND'  => [
                'aliyun' => null,
                'qcloud' => '501361',
            ],
        ];
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
}
