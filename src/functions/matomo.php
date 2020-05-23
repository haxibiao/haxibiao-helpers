<?php

use App\User;

function track_web($category, $action, $name = null, $value = null)
{
    //MATOMO_WEB_ID用于网页事件跟踪，情况和APP里的MATOMO_SITE_ID上下文不同，一般是不同的ID
    $tracker = new \MatomoTracker(env('MATOMO_WEB_ID', 2), env('MATOMO_WEB_URL', "http://matomo.datizhuanqian.com"));
    $tracker->doTrackEvent($category, $action, $name, $value);
}

function app_track_event($category, $action, $name = false, $value = false)
{
    $event['category'] = $category;
    $event['action']   = $action;
    $event['name']     = $name;

    //避免进入的value有对象，不是String会异常
    $event['value'] = $value instanceof String ? $value : false;

    $event = wrapMatomoEventData($event);

    //发送事件数据
    sendMatomoEvent($event);
}

function wrapMatomoEventData($event)
{
    $event['user_id'] = getUniqueUserId();
    $event['ip']      = getIp();

    //传给自定义变量 服务器
    $event['server'] = gethostname();

    if(project_is_dtzq()){
        $event['dimension1'] = getOsSystemVersion(); //设备系统带版本
        $event['dimension2'] = get_referer(); //下载渠道
        $event['dimension3'] = getAppVersion(); //版本
        $event['dimension4'] = getAppVersion() . "(build" . getAppBuild() . ")"; //热更新
        $event['dimension5'] = User::categoryTag(); //新老用户分类
        $event['dimension6'] = getDeviceBrand(); //用户机型品牌
    }

    $event['siteId'] = env('MATOMO_SITE_ID', 1);
    return $event;
}

function sendMatomoEvent(array $event)
{
    $event['cdt'] = time();
    try {
        $client = new \swoole_client(SWOOLE_SOCK_TCP); //同步阻塞？？
        //默认0.1秒就timeout, 所以直接丢给本地matomo:server
        $client->connect('127.0.0.1', env('MATOMO_PROXY_PORT',9502)) or die("swoole connect failed\n");
        $client->set([
            'open_length_check'     => true,
            'package_length_type'   => 'n',
            'package_length_offset' => 0,
            'package_body_offset'   => 2,
        ]);
        $client->send(tcp_pack(json_encode($event)));
    } catch (\Throwable $ex) {
        return false;
    }
    return true;
}

function tcp_pack(string $data): string
{
    return pack('n', strlen($data)) . $data;
}
function tcp_unpack(string $data): string
{
    return substr($data, 2, unpack('n', substr($data, 0, 2), 0)[1]);
}

//开始主要用这个埋点，能快速区别新老用户的事件趋势和分布
function app_track_user_event($action, $name = false, $value = 1)
{
    $user     = getUser(false);
    $category = User::categoryTag($user);
    app_track_event($category, $action, $name, $value);
}

function app_track_reward_video($action, $name = false, $value = false)
{
    app_track_event("激励视频", $action, $name, $value);
}

function app_track_user($action, $name = false, $value = false)
{
    app_track_event("用户行为", $action, $name, $value);
}

function app_track_question($action, $name = false, $value = false)
{
    app_track_event("答题出题", $action, $name, $value);
}

function app_track_task($action, $name = false, $value = false)
{
    app_track_event("任务", $action, $name, $value);
}

function getUniqueUserId()
{
    try {
        return getUserId();
    } catch (\Exception $ex) {
        return getIp();
    }
}

function app_track_app_download()
{
    app_track_user('App下载','app_download');
}

function app_track_send_message()
{
    app_track_user('发送消息');
}