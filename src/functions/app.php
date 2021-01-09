<?php

//FIXME: 这个依赖的只有 haxibiao-media
function getVodConfig(string $key)
{
    $appName = env('APP_NAME');
    $name    = sprintf('vod.%s.%s', $appName, $key);
    return config($name);
}

/**
 * 安装haxibiao下的packages到 app和nova下复制基础代码文件用
 *
 * @param string $pwd 当前代码目录
 * @param boolean $force 是否强制
 * @return void
 */
function copyStubs($pwd, $force = false)
{
    //复制所有app stubs
    foreach (glob($pwd . '/stubs/*.stub') as $filepath) {
        $filename = basename($filepath);
        $dest     = app_path(str_replace(".stub", ".php", $filename));
        if (!file_exists($dest) || $force) {
            copy($filepath, $dest);
        }
    }
    //复制所有nova stubs
    if (!is_dir(app_path('Nova'))) {
        mkdir(app_path('Nova'));
    }
    foreach (glob($pwd . '/stubs/Nova/*.stub') as $filepath) {
        $filename = basename($filepath);
        $dest     = app_path('Nova/' . str_replace(".stub", ".php", $filename));
        if (!file_exists($dest) || $force) {
            copy($filepath, $dest);
        }
    }
}
