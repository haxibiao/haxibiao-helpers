<?php

/**
 * 返回APP的英文名 比如 haxibiao,ainicheng
 *
 * @return string
 */
function app_name()
{
    return config('app.name');
}

/**
 * 返回APP的中文名 比如 哈希表,爱你城
 *
 * @return string
 */
function app_name_cn()
{
    return config('app.name_cn');
}

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
    //复制所有App stubs
    foreach (glob($pwd . '/stubs/*.stub') as $filepath) {
        $filename = basename($filepath);
        $dest     = app_path(str_replace(".stub", ".php", $filename));
        if (!file_exists($dest) || $force) {
            copy($filepath, $dest);
        }
    }

    //复制所有Nova stubs
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

    //复制所有GraphQL stubs
    if (!is_dir(app_path('GraphQL/Directives'))) {
        mkdir(app_path('GraphQL/Directives'), 0777, true);
    }
    if (!is_dir(app_path('GraphQL/Scalars'))) {
        mkdir(app_path('GraphQL/Scalars'), 0777, true);
    }

    foreach (glob($pwd . '/stubs/GraphQL/Directives/*.stub') as $filepath) {
        $filename = basename($filepath);
        $dest     = app_path('GraphQL/Directives/' . str_replace(".stub", ".php", $filename));
        if (!file_exists($dest) || $force) {
            copy($filepath, $dest);
        }
    }
    foreach (glob($pwd . '/stubs/GraphQL/Scalars/*.stub') as $filepath) {
        $filename = basename($filepath);
        $dest     = app_path('GraphQL/Scalars/' . str_replace(".stub", ".php", $filename));
        if (!file_exists($dest) || $force) {
            copy($filepath, $dest);
        }
    }

}
