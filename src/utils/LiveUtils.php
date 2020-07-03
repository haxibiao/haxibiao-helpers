<?php

namespace Haxibiao\Helpers;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Live\V20180801\LiveClient;
use TencentCloud\Live\V20180801\Models\DescribeLiveStreamOnlineListRequest;
use TencentCloud\Live\V20180801\Models\DescribeLiveStreamStateRequest;
use TencentCloud\Live\V20180801\Models\DropLiveStreamRequest;
use TencentCloud\Live\V20180801\Models\ResumeLiveStreamRequest;

/**
 * 直播相关工具类
 */
class LiveUtils
{
    private static $instance = null;
    private $liveClient;

    //单例
    public static function getInstance()
    {
        if (!LiveUtils::$instance) {
            LiveUtils::$instance = new LiveUtils();
        }
        return LiveUtils::$instance;
    }

    /**
     * 可在 https://console.cloud.tencent.com/cam/capi
     * 查看密钥id与key
     */
    public function __construct()
    {
        $secretId    = config('live.secret_id');
        $secretKey   = config('live.secret_key');
        $cred        = new Credential($secretId, $secretKey);
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("live.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $client           = new LiveClient($cred, config('live.location'), $clientProfile);
        $this->liveClient = $client;
    }

    /**
     * 获取正在直播的流名称列表
     * @param $pageNum 当前页码
     * @param $PageSize 页面大小
     */
    public function getStreamOnlineList(int $pageNum = 1, int $PageSize = 10): array
    {
        $req    = new DescribeLiveStreamOnlineListRequest();
        $params = sprintf('{"PageNum":%d,"PageSize":%d}', $pageNum, $PageSize);
        $req->fromJsonString($params);
        $resp = $this->liveClient->DescribeLiveStreamOnlineList($req);
        return json_decode($resp->toJsonString(), true);
    }

    /**
     * 查询单个流的直播状态
     * @param $streamName 流名称
     * @return active、inactive 代表正在直播与未在直播
     */
    public function getLiveSteamStatus(string $streamName): string
    {
        $req    = new DescribeLiveStreamStateRequest();
        $params = sprintf('{"AppName":"live","DomainName":"%s","StreamName":"%s"}', config('live.live_push_url'), $streamName);

        $req->fromJsonString($params);
        $resp   = $this->liveClient->DescribeLiveStreamState($req);
        $result = json_decode($resp->toJsonString(), true);
        return data_get($result, 'StreamState');
    }

    /**
     * 封禁单个直播流
     * @param $streamName 流名称
     */
    public function dropLiveStream(string $streamName)
    {
        $req    = new DropLiveStreamRequest();
        $params = sprintf('{"AppName":"live","DomainName":"%s","StreamName":"%s"}', config('live.live_push_url'), $streamName);
        $req->fromJsonString($params);
        $resp = $this->liveClient->DropLiveStream($req);
        return json_decode($resp->toJsonString(), true);
    }

    /**
     * 恢复单个直播推流
     * @param $streamName 流名称
     */
    public function resumeLiveStream(string $streamName)
    {
        $req    = new ResumeLiveStreamRequest();
        $params = sprintf('{"AppName":"live","DomainName":"%s","StreamName":"%s"}', config('live.live_push_url'), $streamName);
        $req->fromJsonString($params);

        $resp = $this->liveClient->ResumeLiveStream($req);

        return json_decode($resp->toJsonString(), true);
    }
}
