<?php

namespace haxibiao\helpers;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Str;

class InstallCommand extends Command
{

    /**
     * The name and signature of the Console command.
     *
     * @var string
     */
    protected $signature = 'helpers:install';

    /**
     * The Console command description.
     *
     * @var string
     */
    protected $description = '安装 haxibiao/helpers';

    /**
     * Execute the Console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Register Helper Service Provider...');
        $this->registerHelperServiceProvider();
    }

    /**
     * Register the Helper service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerHelperServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->getAppNamespace());

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class," . PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class," . PHP_EOL . "        haxibiao\helpers\HXBHelpersProvider::class," . PHP_EOL,
            file_get_contents(config_path('app.php'))
        ));
    }

    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }
}
