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
        return data_get(space_ucdn_map(), $bucket);
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
