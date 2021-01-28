<?php

use App\Video;

//FIXME: 需要重构到 haxibiao-media
function parse_video($body)
{
    //TODO:: [视频的尺寸还是不完美，后面要获取到视频的尺寸才好处理, 先默认用半个页面来站住]
    $pattern_img_video = '/<img src=\"\/storage\/video\/thumbnail_(\d+)\.jpg\"([^>]*?)>/iu';
    if (preg_match_all($pattern_img_video, $body, $matches)) {
        foreach ($matches[1] as $i => $match) {
            $img_html = $matches[0][$i];
            $video_id = $match;

            $video = Video::find($video_id);
            if ($video) {
                $video_html = '<div class="row"><div class="col-md-6"><div class="embed-responsive embed-responsive-4by3"><video class="embed-responsive-item" controls poster="' . $video->coverUrl . '"><source src="' . $video->url . '" type="video/mp4"></video></div></div></div>';
                $body       = str_replace($img_html, $video_html, $body);
            }
        }
    }
    return $body;
}

function rand_pick_ucdn_domain()
{
    $domains = ucdn_domains();
    return $domains[array_rand($domains)];
}

function ucdn_domains()
{
    return [
        'https://cdn-youku-com.diudie.com/',
        'https://cdn-xigua-com.diudie.com/',
        'https://cdn-iqiyi-com.diudie.com/',
        'https://cdn-v-qq-com.diudie.com/',
        'https://cdn-pptv-com.diudie.com/',
        'https://cdn-leshi-com.diudie.com/',
    ];
}

function get_space_by_ucdn($ucdn_root)
{
    $map = array_flip(space_ucdn_map());
    return $map[$ucdn_root] ?? '';
}

function space_ucdn_map()
{
    return [
        'hanju'      => 'https://cdn-youku-com.diudie.com/',
        'riju'       => 'https://cdn-xigua-com.diudie.com/',
        'meiju'      => 'https://cdn-iqiyi-com.diudie.com/',
        'gangju'     => 'https://cdn-v-qq-com.diudie.com/',
        'blgl'       => 'https://cdn-pptv-com.diudie.com/',
        // 印剧数量少，使用 do spaces cdn domain
        'yinju'      => 'https://yinju.sfo2.cdn.digitaloceanspaces.com/',
        'othermovie' => 'https://cdn-leshi-com.diudie.com/',
        'movieimage' => 'https://image-cdn.diudie.com/',
    ];
}
