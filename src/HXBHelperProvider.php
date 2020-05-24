<?php

namespace haxibiao\helper;

use Illuminate\Support\ServiceProvider;

class HXBHelperProvider extends ServiceProvider
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
