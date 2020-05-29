<?php

//提取正文中的图片URL
function getImageUrlFromHtml($content)
{
    $pattern = "/<img.*?src=['|\"](.*?)['|\"].*?[\/]?>/iu";
    preg_match_all($pattern, $content, $matches);
    return end($matches);
}

//提取正文中的图片路径PATH
function extractImagePaths($body)
{
    $imgs        = [];
    $pattern_img = '/src=\"(.*?)\"/';
    if (preg_match_all($pattern_img, $body, $matches)) {
        $img_urls = $matches[1];
        foreach ($img_urls as $img_url) {
            $imgs[] = parse_url($img_url)['path'];
        }
    }
    return $imgs;
}

function getBase64ImgStream(String $base64url)
{
    $base64ImgData = str_after($base64url, 'base64,');
    return base64_decode($base64ImgData);
}

function matchBase64($source)
{
    //匹配base64
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $source, $res)) {
        // $extension = $res[2];
        //替换base64头部信息
        $base64_string = str_replace($res[1], '', $source);
        return base64_decode($base64_string);
    }
}

//文本中需要图片全地址，方便APP内显示
function convert_img_fullurl($body)
{
    $pattern_img = '/<img(.*?)src=\"(.*?)\"(.*?)>/';
    preg_match_all($pattern_img, $body, $matches);
    $imgurls = $matches[2];
    foreach ($imgurls as $imgurl) {
        if (filter_var($imgurl, FILTER_VALIDATE_URL)) {
            $image = Image::where('path', $imgurl)->first();
            if ($image) {
                $body = str_replace($imgurl, $image->url, $body);
            }
        }
    }
    return $body;
}

function parse_video($body)
{
    //TODO:: [视频的尺寸还是不完美，后面要获取到视频的尺寸才好处理, 先默认用半个页面来站住]
    $pattern_img_video = '/<img src=\"\/storage\/video\/thumbnail_(\d+)\.jpg\"([^>]*?)>/iu';
    if (preg_match_all($pattern_img_video, $body, $matches)) {
        foreach ($matches[1] as $i => $match) {
            $img_html = $matches[0][$i];
            $video_id = $match;

            $video = \App\Video::find($video_id);
            if ($video) {
                $video_html = '<div class="row"><div class="col-md-6"><div class="embed-responsive embed-responsive-4by3"><video class="embed-responsive-item" controls poster="' . $video->coverUrl . '"><source src="' . $video->url . '" type="video/mp4"></video></div></div></div>';
                $body       = str_replace($img_html, $video_html, $body);
            }
        }
    }
    return $body;
}

function parse_image($body, $environment = null)
{
    //检测本地或GQL没图的时候取线上的
    $environment = $environment ?: \App::environment('local');

    if ($environment) {
        $pattern_img = '/<img(.*?)src=\"(.*?)\"(.*?)>/';
        preg_match_all($pattern_img, $body, $matches);
        $imgs = $matches[2];
        foreach ($imgs as $img) {
            $image = \App\Image::where('path', $img)->first();
            if ($image) {
                $body = str_replace($img, $image->url, $body);
            }
        }
    }
    return $body;
}

function get_qq_pic($qq)
{
    return 'https://q.qlogo.cn/headimg_dl?bs=qq&dst_uin=' . $qq . '&src_uin=qq.com&fid=blog&spec=100';
}

function get_qzone_pic($qq)
{
    return 'https://qlogo2.store.qq.com/qzonelogo/' . $qq . '/1/' . time();
}
