<?php

namespace Haxibiao\Helpers\utils;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FFMpegUtils
{
    //视频截图存放地址
    const SCREEN_SHOT_PATH = 'storage/app/screenshot/%s.jpg';

    private function __construct()
    {
    }

    private function __clone()
    {
    }


    /**
     * @param start 00:21:24.00 21分24秒 类似这种形式
     * @param seconds 截取多少秒
     * @param path 可以是本地path 或者是url
     */
    public static function M3u8ConvertIntoMp4($path, $start, $seconds, $savePath)
    {
        $localSavePath = storage_path('app/public/' . $savePath);
        $command       = "ffmpeg -i {$path}  -acodec copy -vcodec copy -ss {$start} -t  $seconds  $localSavePath";
        exec($command);
        $fileid = VodUtils::Upload($localSavePath);
        unlink($localSavePath);
        return $fileid;
    }

    /**
     * 获取视信息
     * @param $streamPath
     * @return |null
     */
    public static function getStreamInfo($streamPath)
    {
        $ffprobe = app('ffprobe');
        $stream  = $ffprobe->streams($streamPath)->videos()->first();
        return $stream ? $stream->all() : null;
    }

    /**
     * 截取图片
     * @param $streamPath
     * @param $fromSecond
     * @param $name
     * @return mixed
     */
    public static function saveCover($streamPath, $fromSecond, $name)
    {
        try {
            $ffmpeg = app('ffmpeg');

            $video = $ffmpeg->open($streamPath);
            $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($fromSecond)); //提取第几秒的图像
            //FIXME::答赚和答妹没有用makeVideoCover，这个方法也不会用到，目前只有工厂项目用了这个方法。
            if (!in_array(env("APP_NAME"), ["datizhuanqian", "damei"])) {
                $frame->save($name);
                return $name;
            }
            $frame->save('/tmp/' . $name . '.jpg');

            //上传到Cos
            $cosPath = sprintf(self::SCREEN_SHOT_PATH, $name);
            if (!is_prod_env()) {
                $cosPath = 'temp/' . $cosPath;
            }
            $cosDisk = Storage::cloud();
            $cosDisk->put($cosPath, file_get_contents('/tmp/' . $name . '.jpg'));
            return $cosDisk->url($cosPath);
        } catch (\Exception $ex) {
            //截图失败，返回null

            return null;
        }
    }

    /**
     *  @param:path 可是本地 or url
     */
    public static function addMediaMetadata($path, $comment = null, $title = null)
    {
        $fileName = Str::random(12) . '.mp4';
        // 输出文件放系统临时文件夹，随系统自动清理
        $outputFilePath  = sys_get_temp_dir() . '/' . $fileName;
        /**
         * 参数说明
         * -i 输入文件
         * -y 强制覆盖输出目录下同名文件
         * -c copy 直接复制第一个视频的音频流，不进行编码
         * -metadata 修改medata信息
         * -f 文件的输出格式
         */
        $command = "ffmpeg -i {$path} -y -c copy  -metadata ";
        if ($comment) {
            $command .= " comment={$comment}";
        }
        if ($title) {
            $command .= " title={$title}";
        }
        exec($command . ' -f  mp4 ' . $outputFilePath);
        return $outputFilePath;
    }

    /**
     *  @param:path 可是本地 or url
     */
    public static function getMediaMetadata($path)
    {
        exec('ffprobe -v quiet -show_format -show_streams -print_format json ' . $path, $info);

        $info = json_decode(implode(" ", $info), true);
        return $info;
    }


    /**
     * 横屏视频转竖屏视频分辨率
     */
    public static function ToPortrait($url, $width, $height)
    {
        $saveHeight = ceil($height * 3.16);
        $location   = ($saveHeight - $height) / 2;
        $extendsion = '.' . substr($url, strripos($url, ".") + 1);
        $fileName   = uniqid() . $extendsion;
        $savePath   = storage_path('app/public/videos/' . $fileName);
        $command    = "ffmpeg -i {$url} -vf 'scale={$width}:{$height},
        pad={$width}:{$saveHeight}:0:{$location}'  {$savePath}";
        exec($command);
        return $savePath;
    }
}
