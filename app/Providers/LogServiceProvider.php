<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 11/24/2017
 * Time: 9:20 AM
 */

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Configure logging on boot.
     *
     * @return void
     */
    public function boot()
    {
        $maxFiles = MAX_FILE_LOG;
        $handlers[] = (new RotatingFileHandler(storage_path("logs/lumen.log"), $maxFiles))
            ->setFormatter(new LineFormatter(null, null, true, true));

        $this->app['log']->setHandlers($handlers);
    }

    /**
     * Register the log service.
     *
     * @return void
     */
    public function register()
    {
        // Log binding already registered in vendor/laravel/lumen-framework/src/Application.php.
    }
}