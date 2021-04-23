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
