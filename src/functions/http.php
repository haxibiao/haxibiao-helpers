<?php

use GuzzleHttp\Client;

function baseclient()
{
    return new Client([
        'time_out' => 5,
    ]);
}

function upload_file($url, $fileName, $file, $otherData = [])
{
    $postData = [];
    foreach ($otherData as $key => $value) {
        $postData[] = [
            'name'     => $key,
            'contents' => $value,
        ];
    }
    $baseClient = baseclient();
    $rsp        = $baseClient->request('POST', $url, [
        'multipart' => array_merge([[
            'name'     => $fileName,
            'contents' => fopen($file, 'r'),
        ]], $postData),
    ]);
    return $rsp->getBody()->getContents();
}
