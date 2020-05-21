<?php

use Laravel\Nova\Nova;

/**
 * 使用场景:Laravel Nova
 * 全局nova辅助函数
 */

function nova_fields_show(array $fields, $callback = 'exceptOnForms')
{
    foreach ($fields as $field) {
        $field->$callback();
    }
    return $fields;
}

function nova_resource_uri($resource)
{
    return Nova::path() . '/resources/' . $resource::uriKey() . '/' . $resource->getRouteKey();
}
