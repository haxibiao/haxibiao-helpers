<?php

namespace Haxibiao\Helpers\utils;

use QcloudApi;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Vod\V20180717\Models\PullUploadRequest;
use TencentCloud\Vod\V20180717\Models\PushUrlCacheRequest;
use TencentCloud\Vod\V20180717\VodClient;
use Vod\Model\VodUploadRequest;

/**
 * 腾讯VOD视频上传助手类
 *
 * Class VodUtils
 * @package Haxibiao\Helpers
 */
class VodUtils
{

    public static function Upload($localFilePath)
    {
        $req                = new VodUploadRequest();
        $req->ClassId       = config('vod.class_id');
        $req->Procedure     = 'SimpleAesEncryptPreset';
        $req->MediaFilePath = $localFilePath;
        $rsp                = app('vodUpload')->upload("ap-guangzhou", $req);
        $fileId             = $rsp->FileId;
        return $fileId;
    }

    /**
     * VOD视频资源预热
     */
    public static function pushUrlCacheWithVODUrl($url)
    {
        //VOD预热
        $cred        = new Credential(config('vod.secret_id'), config('vod.secret_key'));
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("vod.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);

        $client = new VodClient($cred, "ap-guangzhou", $clientProfile);
        $req    = new PushUrlCacheRequest();
        $params = '{"Urls":["' . $url . '"]}';

        $req->fromJsonString($params);
        $resp = $client->PushUrlCache($req);

        return $resp->toJsonString();
    }

    public static function PullUpload($url)
    {
        try {
            $cred        = new Credential(config('vod.secret_id'), config('vod.secret_key'));
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vod.tencentcloudapi.com");
            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VodClient($cred, "", $clientProfile);
            $req    = new PullUploadRequest();
            $params = array(
                "MediaUrl" => $url,
                "ClassId"  => config('vod.class_id'),
            );
            $req->fromJsonString(json_encode($params));
            $resp = $client->PullUpload($req);
            return $resp->toJsonString();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private static function initVod()
    {
        $config = null;

        //在这里判断是为了兼容其他使用 App 使用了 vod 文件中 SecretId ，我并不确定其他项目的 .env 中是否设置了 SecretId
        if (config('app.name') == 'ablm') {
            $config = [
                'SecretId'      => config('vod.ablm.secret_id'),
                'SecretKey'     => config('vod.ablm.secret_key'),
                'RequestMethod' => 'POST',
            ];
        } else {
            $config = [
                'SecretId'      => config('vod.secret_id'),
                'SecretKey'     => config('vod.secret_key'),
                'RequestMethod' => 'POST',
            ];
        }

        return QcloudApi::load(QcloudApi::MODULE_VOD, $config);
    }

    private static function retryVodApi($apiAction, $params)
    {
        $vod = self::initVod();
        for ($retry = 0; $retry < 3; $retry++) {
            if ($retry > 0) {
                echo "$apiAction retry at " . $retry;
            }
            $response = $vod->$apiAction($params);
            if ($response == false) {
                $error = $vod->getError();
                echo "$apiAction failed, code: " . $error->getCode() .
                ", message: " . $error->getMessage() .
                "ext: " . var_export($error->getExt(), true) . "\n";
                continue;
            } else {
                return $response;
            }
        }
    }

    public static function getTaskInfo($taskId)
    {
        $params = [
            'vodTaskId' => $taskId,
        ];
        return self::retryVodApi('GetTaskInfo', $params);
    }

    public static function getTaskList()
    {
        $params = [
            'status' => "WAITING",
        ];
        $results['WAITING'] = self::retryVodApi('GetTaskList', $params);
        $params             = [
            'status' => "PROCESSING",
        ];
        $results['PROCESSING'] = self::retryVodApi('GetTaskList', $params);
        $params                = [
            'status' => "FINISH",
        ];
        $results['FINISH'] = self::retryVodApi('GetTaskList', $params);

        return $results;
    }

    public static function deleteVodFile($fileId)
    {
        $params = [
            'fileId'     => $fileId,
            'isFlushCdn' => 1,
            'priority'   => 1,
        ];
        return self::retryVodApi('DeleteVodFile', $params);
    }

    public static function getVideoInfo($fileId)
    {
        $params = [
            'fileId' => $fileId,
        ];
        return self::retryVodApi('GetVideoInfo', $params);
    }

    public static function getVodInfoByFileName($fileName)
    {
        $params = [
            'fileName' => $fileName,
        ];
        return self::retryVodApi('DescribeVodPlayInfo', $params);
    }

    public static function processVodFile($fileId)
    {
        $params = [
            'fileId'                            => $fileId,
            'snapshotByTimeOffset.definition'   => 10,
            'snapshotByTimeOffset.timeOffset.1' => 1000,
            'coverBySnapshot.definition'        => 10,
            'coverBySnapshot.positionType'      => 'Time',
            'coverBySnapshot.position'          => 2, // 第2秒
            // 'sampleSnapshot.definition' => 20043,
            // 'animatedGraphics.definition' => 20000,
            // 'animatedGraphics.startTime' => 3,
            // 'animatedGraphics.endTime' => 5,
        ];
        return self::retryVodApi('ProcessFile', $params);
    }

    /**
     * 截取封面图，主要给VOD后台使用
     * @param $fileId
     * @param null $duration
     * @return mixed
     */
    public static function makeCover($fileId)
    {

        $params = array_merge([
            'fileId' => $fileId,
        ], [
            'coverBySnapshot.definition'   => 20109, //截取封面
            'coverBySnapshot.positionType' => 'Percent',
            'coverBySnapshot.position'     => 20, // 第2秒
        ]);

        return self::retryVodApi('ProcessFile', $params);
    }

    public static function makeLanscapeCoverAndSnapshots($fileId)
    {
        $params = [
            'fileId'                            => $fileId,
            'snapshotByTimeOffset.definition'   => 20080, //这个模板设置为截图300*200， 手机视频肯定被压扁了
            'snapshotByTimeOffset.timeOffset.1' => 1000,
            'snapshotByTimeOffset.timeOffset.2' => 2000,
            'snapshotByTimeOffset.timeOffset.3' => 3000,
            'snapshotByTimeOffset.timeOffset.4' => 4000,
            'snapshotByTimeOffset.timeOffset.5' => 5000,
            'snapshotByTimeOffset.timeOffset.6' => 6000,
            'snapshotByTimeOffset.timeOffset.7' => 7000,
            'snapshotByTimeOffset.timeOffset.8' => 8000,
            'snapshotByTimeOffset.timeOffset.9' => 9000,
            'coverBySnapshot.definition'        => 10,
            'coverBySnapshot.positionType'      => 'Time',
            'coverBySnapshot.position'          => 2, // 第2秒
        ];
        return self::retryVodApi('ProcessFile', $params);
    }

    public static function genGif($fileId)
    {
        $params = [
            'fileId'                        => $fileId,
            'animatedGraphics.definition.2' => 20000,
            'animatedGraphics.startTime'    => 0,
            'animatedGraphics.endTime'      => 2,
        ];
        return self::retryVodApi('ProcessFile', $params);
    }

    public static function simpleProcessFile($fileId)
    {
        $params = [
            'file.id'   => $fileId,
            'inputType' => 'SingleFile',
            'procedure' => 'QCVB_SimpleProcessFile(1, 1, 10, 10)', //这个系统预置流程简单实用，转码，水印，封面，截图
        ];
        return self::retryVodApi('RunProcedure', $params);
    }

    public static function takeSnapshotsByTime($fileId)
    {
        $params = [
            'fileId'       => $fileId,
            'definition'   => 10, //正常比例缩放
            'timeOffset.1' => 1000,
            'timeOffset.2' => 2000,
            'timeOffset.3' => 3000,
            'timeOffset.4' => 4000,
            'timeOffset.5' => 5000,
            'timeOffset.6' => 6000,
            'timeOffset.7' => 7000,
            'timeOffset.8' => 8000,
            'timeOffset.9' => 9000,
        ];
        return self::retryVodApi('CreateSnapshotByTimeOffset', $params);
    }

    public static function pullEvents()
    {
        $params = [];
        return self::retryVodApi('PullEvent', $params);
    }

    public static function confirmEvents($msgHandles = [])
    {
        $params = [];
        $i      = 0;
        foreach ($msgHandles as $msgHandle) {
            $params['msgHandle.' . $i] = $msgHandle;
            $i++;
        }
        return self::retryVodApi('ConfirmEvent', $params);
    }

    public static function makeCoverAndSnapshots($fileId, $duration = null)
    {
        $maxDuration = $duration > 9 ? 9 : $duration;
        $timeOffsets = [];
        for ($seconds = 1; $seconds <= $maxDuration; $seconds++) {
            $timeOffsets['snapshotByTimeOffset.timeOffset.' . $seconds] = $seconds * 1000;
        }
        $params = array_merge(
            [
                'fileId'                          => $fileId,
                'snapshotByTimeOffset.definition' => 10, //截取9张正常缩放的图片
            ],
            $timeOffsets,
            [
                'coverBySnapshot.definition'   => 10, //截取封面
                'coverBySnapshot.positionType' => 'Time',
                'coverBySnapshot.position'     => 2, // 第2秒截图做默认封面
            ]
        );
        return self::retryVodApi('ProcessFile', $params);
    }

    public static function convertVodFile($fileId)
    {
        $params = [
            'fileId'       => $fileId,
            'isScreenshot' => 0,
            'isWatermark'  => 0,
        ];
        return self::retryVodApi('ConvertVodFile', $params);
    }
}
