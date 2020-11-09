<?php
namespace Haxibiao\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class SensitiveFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'SensitiveUtils';
    }
}
