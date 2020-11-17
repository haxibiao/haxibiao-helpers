<?php
namespace Haxibiao\Helpers;

use Illuminate\Support\ServiceProvider;
use Haxibiao\Helpers\utils\SensitiveUtils;
use Haxibiao\Helpers\Console\InstallCommand;

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

        $this->app->singleton('SensitiveUtils', function ($app) {
            return SensitiveUtils::init()->setTreeByFile(__DIR__ . '/utils/Sensitive/words.txt')
                ->interference(['&', '*', '#', ' ']);
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
