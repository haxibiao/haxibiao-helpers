<?php

//FIXME: 这个依赖的只有 haxibiao-media
function getVodConfig(string $key)
{
    $appName = env('APP_NAME');
    $name    = sprintf('tencentvod.%s.%s', $appName, $key);
    return config($name);
}

function stopfunction($name)
{
    // \App\FunctionSwitch::close_function($name);
}
