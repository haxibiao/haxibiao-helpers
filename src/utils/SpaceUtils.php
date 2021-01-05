<?php

namespace Haxibiao\Helpers\utils;

use GuzzleHttp\Client;

class SpaceUtils
{

    public static function getHeaders()
    {
        return $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . config('space.token')
        ];
    }

    /**
     * 获取所有space endpoint
     */
    public static function endpoints()
    {
        $client = new Client();
        $resp   = $client->get('https://api.digitalocean.com/v2/cdn/endpoints', [
            'headers' => self::getHeaders()
        ]);
        $endpoints = json_decode($resp->getBody()->getContents(), true);

        return $endpoints;
    }

    /**
     * 自定义method 
     * @param space bucket
     * @return space cdn id
     */

    public static function getCDNId($bucket)
    {
        $array = [
            'othermovie' => '064f1d97-5968-4a1d-a6b0-461de316bd2d',
            'yinju'     =>  '06a9e2be-5547-4bcb-acb6-e8429d446e64',
            'blgl'      =>  '0e958cce-750b-4d40-9bc9-210a3dc50b27',
            'tai'       =>  '0eee0260-26a8-4f18-afe3-d681381378bd',
            'gangju'    =>  '246c5dce-6fc5-40c8-b4ba-c4a55ece9e41',
            'jieshuo'   =>  '39c1b754-20a0-4d6a-bf18-f2ed2abe2c7c',
            'mjhj'      =>  '57489abd-6b7a-4e97-9e99-f44afe19e7ee',
            'riju'      =>  '683cd67a-b10a-4b37-a828-a3f82c55a95a',
            'movieimage' => '80645b72-5fb0-4988-860c-115e128b9b84',
            'hanju'     =>  'e0aed1f2-c191-4c2b-8895-b88e4717cca7',
            'meiju'     =>  'e77bb765-144d-4433-b29d-a68f63aa40fd',
        ];
        return $array[$bucket] ?? 'null';
    }

    /**
     * 刷新space cnd 缓存
     */
    public static function refreshCache(array $paths, $bucket)
    {
        $curl = curl_init();
        $json = [
            'files' => $paths
        ];
        $query = json_encode($json);
        $endpointId = self::getCDNId($bucket);
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.digitalocean.com/v2/cdn/endpoints/{$endpointId}/cache",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . config('space.token'),
                "Content-Type: application/json"
            ),
        ));


        $response = curl_exec($curl);
        $respCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $respCode == 204 ? true : false;
    }
}
