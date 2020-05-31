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
