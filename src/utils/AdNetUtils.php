<?php

namespace Haxibiao\Helpers\utils;

use GuzzleHttp\Client;

class AdNetUtils
{
    const REPORT_LIST_URL = 'https://adnet.qq.com/eros/report/strategy_table_data';

    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function reportList($headers, array $date, array $dimensions, $page = 1, $pageSize = 10)
    {
        $maxPageSize = 100;
        $rsp         = $this->client->request('POST', self::REPORT_LIST_URL, [
            'headers' => $headers,
            'body'    => json_encode([
                'start_date' => head($date),
                'end_date'   => end($date),
                'biz_filter' => [
                    'medium'         => [],
                    'placement_type' => [],
                    'placement'      => [],
                ],
                "group_by"   => $dimensions,
                "page"       => $page,
                "page_size"  => $pageSize > $maxPageSize ? $maxPageSize : $pageSize,
            ])]);
        $result = $rsp->getBody()->getContents();
        

        return json_decode($result,true);
    }

}
