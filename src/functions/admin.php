<?php

//FIXME: 重构 haxibiao-config

function get_top_categoires($top_categoires)
{
    //原来网站还需要合并点编辑置顶的??
    return $top_categoires;
}

function get_top_categories_count()
{
    //原来网站置顶7个分类在首页？
    return 7;
}

function get_top_articles()
{
    return [];
}

function get_stick_categories($all = false, $index = false)
{
    //以前置顶用
    return [];

}

function get_stick_video_categories($all = false, $index = false)
{
    //以前置顶用
    return [];
}

function stick_category($data, $auto = false)
{
    //以前置顶用

}

function stick_video_category($data, $auto = false)
{
    //以前置顶用

}

function stick_article($data, $auto = false)
{
    //以前置顶用

}

function get_stick_articles($position = '', $all = false)
{
    //以前返回不同位置置顶文章用
    return [];
}

function get_stick_videos($position = '', $all = false)
{
    //以前返回不同位置置顶视频用
    return [];
}

function stick_video($data, $auto = false)
{
    //以前置顶视频用
}
