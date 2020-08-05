<?php


namespace Haxibiao\Helpers\utils;


use GuzzleHttp\Client;

/**
 * Class BaiduUtils of 百度地图小工具👸
 * @package Haxibiao\Helpers\utils
 */
class BaiduUtils
{
    const REVERS_GEOCODING_URL = 'http://api.map.baidu.com/reverse_geocoding/v3';


    /**
     * 全球逆地理编码
     *
     * 百度地图API: https://lbsyun.baidu.com/index.php?title=webapi/guide/webservice-geocoding-abroad
     *
     * @param $lat 纬度（必填）
     * @param $lng 经度（必填）
     * @param string $coordType 输入坐标的坐标系（选填）
     * @return mixed
     * @throws Exception
     */
    public static function reverseGeocoding($lat, $lng, $coordType = 'bd09ll')
    {
        //1.前置准备
        $location = $lat . ',' . $lng;

        if (is_null($lat) || is_null($lng)) {
            throw new Exception('请传入经度 或 纬度');
        }

        $requestParams = self::getRequestParams($location, $coordType);

        //2.发送请求
        $http = new GuzzleHttp\Client();

        $response = $http->get(self::REVERS_GEOCODING_URL, $requestParams);

        //3.返回结果
        return json_decode($response->getBody(), true);
    }

    /**
     * 获取全球逆地理编码请求参数
     *
     * @param $location 坐标
     * @param $coordType 输入坐标的坐标系
     * @return array
     */
    protected static function getRequestParams($location, $coordType)
    {
        return [
            'query' => [
                //申请注册的key
                'ak' => config('baidu.ak'),

                //输出格式
                'output' => 'json',

                //经纬度
                'location' => $location,

                'coordtype' => $coordType,
            ]
        ];
    }
}