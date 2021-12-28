<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

//FIXME: 重构到 haxibiao-media
// 文本中需要图片全地址，方便APP内显示
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

function parse_image($body, $environment = null)
{
    //任何环境，都取image->url的cdn地址，没有的修复cdn图片！
    $pattern_img = '/<img(.*?)src=\"(.*?)\"(.*?)>/';
    preg_match_all($pattern_img, $body, $matches);
    $imgs = $matches[2];
    foreach ($imgs as $img) {
        $image = \App\Image::where('path', parse_url($img, PHP_URL_PATH))->first();
        if ($image) {
            $body = str_replace($img, $image->url, $body);
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

/****
 * 多图合并为一张图片
 * 类似微信群头像
 * pic_list 一组图片，最大9个
 ****/
function mergeImages($pic_list)
{
    $pic_list   = array_slice($pic_list, 0, 9); // 只操作前9个图片
    $bg_w       = 150; // 背景图片宽度
    $bg_h       = 150; // 背景图片高度
    $pic_count  = count($pic_list);
    $lineArr    = array(); // 需要换行的位置
    $space_x    = 3;
    $space_y    = 3;
    $line_x     = 0;
    $background = imagecreatetruecolor($bg_w, $bg_h); // 背景图片

    switch ($pic_count) {
        case 1: // 正中间
            $start_x = intval($bg_w / 4); // 开始位置X
            $start_y = intval($bg_h / 4); // 开始位置Y
            $pic_w   = intval($bg_w / 2); // 宽度
            $pic_h   = intval($bg_h / 2); // 高度
            break;

        case 2: // 中间位置并排
            $start_x    = 2;
            $start_y    = 3;
            $pic_w      = intval($bg_w / 2) - 3;
            $pic_h      = intval($bg_h / 2) - 3;
            $space_x    = 1;
            $background = imagecreatetruecolor($bg_w, $pic_h); // 背景图片
            break;

        case 3:
            $start_x = 4; // 开始位置X
            $start_y = 4; // 开始位置Y
            $pic_w   = intval($bg_w / 2) - 5; // 宽度
            $pic_h   = intval($bg_h / 2) - 5; // 高度
            $lineArr = array(2);
            $line_x  = 4;
            break;

        case 4:
            $start_x = 4; // 开始位置X
            $start_y = 5; // 开始位置Y
            $pic_w   = intval($bg_w / 2) - 5; // 宽度
            $pic_h   = intval($bg_h / 2) - 5; // 高度
            $lineArr = array(3);
            $line_x  = 4;
            break;

        case 5:
            $start_x = 30; // 开始位置X
            $start_y = 30; // 开始位置Y
            $pic_w   = intval($bg_w / 3) - 5; // 宽度
            $pic_h   = intval($bg_h / 3) - 5; // 高度
            $lineArr = array(3);
            $line_x  = 5;
            break;

        case 6:
            $start_x = 5; // 开始位置X
            $start_y = 30; // 开始位置Y
            $pic_w   = intval($bg_w / 3) - 5; // 宽度
            $pic_h   = intval($bg_h / 3) - 5; // 高度
            $lineArr = array(4);
            $line_x  = 5;
            break;

        case 7:
            $start_x = 53; // 开始位置X
            $start_y = 5; // 开始位置Y
            $pic_w   = intval($bg_w / 3) - 5; // 宽度
            $pic_h   = intval($bg_h / 3) - 5; // 高度
            $lineArr = array(2, 5);
            $line_x  = 5;
            break;

        case 8:
            $start_x = 30; // 开始位置X
            $start_y = 5; // 开始位置Y
            $pic_w   = intval($bg_w / 3) - 5; // 宽度
            $pic_h   = intval($bg_h / 3) - 5; // 高度
            $lineArr = array(3, 6);
            $line_x  = 5;
            break;

        case 9:
            $start_x = 5; // 开始位置X
            $start_y = 5; // 开始位置Y
            $pic_w   = intval($bg_w / 3) - 5; // 宽度
            $pic_h   = intval($bg_h / 3) - 5; // 高度
            $lineArr = array(4, 7);
            $line_x  = 5;
            break;
    }

    foreach ($pic_list as $k => $pic_path) {
        $kk = $k + 1;

        if (in_array($kk, $lineArr)) {
            $start_x = $line_x;
            $start_y = $start_y + $pic_h + $space_y;
        }

        if ($pic_count == 3 && $k == 1) {
            $start_x = 77;
            $start_y = 4;
            $pic_w   = 70;
            $pic_h   = 142;
        }

        if ($pic_count == 3 && $k == 2) {
            $start_x = 4;
            $start_y = 76;
            $pic_w   = 70;
            $pic_h   = 70;
        }

        $pathInfo = pathinfo($pic_path);
        info($pathInfo['extension']);
        info($pic_path);
        switch (strtolower($pathInfo['extension'])) {
            case 'jpg':

            case 'jpeg':
                $imagecreatefromjpeg = 'imagecreatefromjpeg';
                break;

            case 'png':
                $imagecreatefromjpeg = 'imagecreatefrompng';
                break;

            case 'gif':

            default:
                $imagecreatefromjpeg = 'imagecreatefromstring';
                $pic_path            = file_get_contents($pic_path);
                break;
        }

        $resource = $imagecreatefromjpeg($pic_path);

        // $start_x,$start_y copy图片在背景中的位置
        // 0,0 被copy图片的位置
        // $pic_w,$pic_h copy后的高度和宽度

        $color = imagecolorallocate($background, 202, 201, 201); // 为真彩色画布创建白色背景，再设置为透明

        imagefill($background, 0, 0, $color);

        imageColorTransparent($background, $color);

        imagecopyresized($background, $resource, $start_x, $start_y, 0, 0, $pic_w, $pic_h, imagesx($resource),

            // imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            imagesy($resource)); // 最后两个参数为原始图片宽度和高度，倒数两个参数为copy时的图片宽度和高度

        $start_x = $start_x + $pic_w + $space_x;

    }

    header("Content-type: image/jpg");

    //gd转为base64格式
    ob_start();
    imagejpeg($background);
    $image_data = ob_get_contents();
    ob_end_clean();
    $source = base64_encode($image_data);
    if ($base64 = matchBase64($source)) {
        $source = $base64;
    }

    // 原图临时文件
    $imageMaker = Image::make($source);
    $mime       = explode('/', $imageMaker->mime());
    $extension  = end($mime) ?? 'png';
    $imageName  = uniqid();
    $filename   = $imageName . '.' . $extension;
    $tmp_path   = storage_path('/tmp/' . $filename);
    $imageMaker->save($tmp_path);

    //上传返回url
    $cloud_path = 'storage/app-' . env('APP_NAME') . '/mergeImages/' . $filename . '_' . time() . '.png';
    Storage::cloud()->put($cloud_path, $imageMaker->__toString());
    //删除临时图片
    File::delete($tmp_path);
    return cdnurl($cloud_path);
}
