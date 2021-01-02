<?php

namespace Haxibiao\Helpers\utils;

use GuzzleHttp\Client;

class UcloudUtils
{

    public static function WarmUpUrl($url)
    {
        $params = [
            'Action'    => 'PrefetchNewUcdnDomainCache',
            'PublicKey' => config('ucloud.PublicKey'),
            'UrlList.0' => $url,
        ];
        $client = new Client();
        $resp   = $client->get('https://api.ucloud.cn/', [
            'query' => array_merge($params, ['Signature' => self::getSignature($params)]),
        ]);
        return $resp->getBody()->getContents();
    }

    public static function getSignature($params)
    {
        $result = "";
        foreach ($params as $key => $value) {
            $result .= $key . $value;
        }
        $result .= config('ucloud.PrivateKey');
        return sha1($result);
    }

    /**
     * 获得长视频的cdn域名
     *
     * @param string $bucket
     * @return string
     */
    public static function getCDNDomain($bucket)
    {
        $bucket = trim($bucket);
        return data_get([
            'hanju'      => 'https://cdn-youku-com.diudie.com/',
            'riju'       => 'https://cdn-xigua-com.diudie.com/',
            'meiju'      => 'https://cdn-iqiyi-com.diudie.com/',
            'gangju'     => 'https://cdn-v-qq-com.diudie.com/',
            'blgl'       => 'https://cdn-pptv-com.diudie.com/',
            // 印剧数量少，使用 do spaces cdn domain
            'yinju'      => 'https://yinju.sfo2.cdn.digitaloceanspaces.com/',
            'othermovie' => 'https://cdn-leshi-com.diudie.com/',
            'movieimage' => 'https://image-cdn.diudie.com/',
        ], $bucket);
    }

    public static function refreshCache($url)
    {
        $params = [
            'Action'    => 'RefreshNewUcdnDomainCache',
            'PublicKey' => config('ucloud.PublicKey'),
            'Type'      => 'file',
            'UrlList.0' => $url,
        ];
        $client = new Client();
        $resp   = $client->get('https://api.ucloud.cn/', [
            'query' => array_merge($params, [
                'Signature' => self::getSignature($params),
            ]),
        ]);
        return $resp->getBody()->getContents();
    }
}
