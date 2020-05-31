<?php

namespace haxibiao\helpers;

use GuzzleHttp\Client;

/**
 * 旗下站点绑定授权，提现等工具方法
 */
class SiteUtils
{
    private $client;

    public function __construct($domain)
    {
        $this->client = new Client(['base_uri' => $this->getEndPoint($domain)]);
    }

    /**
     * Site授权
     *
     * @param string $uuid
     * @return array
     */
    public function auth($uuid)
    {

        $response = $this->client->request('POST', 'user/auth', [
            'http_errors' => false,
            'form_params' => [
                'app'  => config('app.name_cn'),
                'uuid' => $uuid,
            ],
        ]);
        $contents = $response->getbody()->getContents();

        return empty($contents) ? [] : json_decode($contents, true);
    }

    /**
     * 转账到Site
     *
     * @param string $siteUserId
     * @param string $userId
     * @param float $amount
     * @return array
     */
    public function transfer($siteUserId, $userId, $amount)
    {
        $response = $this->client->request('POST', 'order/createOrder', [
            'http_errors' => false,
            'form_params' => [
                'app'         => config('app.name_cn'),
                'app_user_id' => $userId,
                'user_id'     => $siteUserId,
                'amount'      => $amount,
            ],
        ]);

        $contents = $response->getbody()->getContents();

        return empty($contents) ? [] : json_decode($contents, true);
    }

    /**
     * 绑定Site
     *
     * @param string $account
     * @param string $password
     * @param int $userId
     * @return array
     */
    public function bindByAccount($account, $password)
    {
        $response = $this->client->request('POST', 'user/bindByAccount', [
            'http_errors' => false,
            'form_params' => [
                'app'      => config('app.name_cn'),
                'account'  => $account,
                'password' => $password,
            ],
        ]);

        $contents = $response->getbody()->getContents();

        return empty($contents) ? [] : json_decode($contents, true);
    }

    public function getEndPoint($domain)
    {
        if (is_prod_env()) {
            $subDomain = '';
        } else if (is_staging_env()) {
            $subDomain = 'staging.';
        } else {
            $subDomain = 'l.';
        }

        return sprintf('http://%s%s/api/', $subDomain, $domain);
    }

    /**
     * 通过ID获取Site用户信息
     *
     * @param ID $userId
     * @return array
     */
    public function userinfo($userId)
    {
        $response = $this->client->request('POST', 'user/getUser', [
            'http_errors' => false,
            'form_params' => [
                'user_id' => $userId,
            ],
        ]);

        $contents = $response->getbody()->getContents();

        return empty($contents) ? [] : json_decode($contents, true);
    }

    /**
     * 同步Site用户UUID
     *
     * @param integer $userId
     * @param string $uuid
     * @return array
     */
    public function syncUserUUID(int $userId, string $uuid)
    {
        $response = $this->client->request('POST', 'user/updateUUID', [
            'http_errors' => false,
            'form_params' => [
                'user_id' => $userId,
                'uuid'    => $uuid,
            ],
        ]);

        $contents = $response->getbody()->getContents();

        return empty($contents) ? [] : json_decode($contents, true);
    }
}
