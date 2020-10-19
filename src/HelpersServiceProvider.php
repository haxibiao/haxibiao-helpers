<?php

namespace Haxibiao\Helpers;

use Haxibiao\Helpers\utils\SensitiveUtils;
use Illuminate\Support\ServiceProvider;

class HelpersServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $src_path = __DIR__;
        foreach (glob($src_path . '/functions/*.php') as $filename) {
            require_once $filename;
        }
        foreach (glob($src_path . '/utils/*/*.php') as $filename) {
            require_once $filename;
        }
        // Register Commands
        $this->commands([
            InstallCommand::class,
        ]);

        $this->app->singleton('SensitiveUtils', function($app)
        {
            return SensitiveUtils::init()
                ->setTreeByFile(__DIR__.'/utils/Sensitive/words.txt');
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
