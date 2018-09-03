<?php

use Illuminate\Http\Request;

require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

$app->instance(Request::class, Request::capture());

$app->configure('auth');
$app->configure('jwt');
$app->configure('filesystems');
$app->configure('repository');
$app->configure('elasticsearch');
$app->configure('mail');
$app->configure('queue');
$app->configure('variables');
$app->configure('logging');
$app->configure('es');


$app->withFacades(true, [
    /**
     * Class alias here
     */
    Illuminate\Support\Facades\Notification::class => 'Notification',
]);

$app->alias('mailer', \Illuminate\Contracts\Mail\Mailer::class);
$app->alias('Image', \Intervention\Image\Facades\Image::class);

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Filesystem\Factory::class,
    function ($app) {
        return new Illuminate\Filesystem\FilesystemManager($app);
    }
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//    App\Http\Middleware\ExampleMiddleware::class
// ]);

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
]);

 $app->routeMiddleware([
     'auth' => App\Http\Middleware\Authenticate::class,
     'jwt.auth' => Tymon\JWTAuth\Http\Middleware\Authenticate::class,
     'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class,
 ]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/
//dd(\App\Libraries\Helper::getRequestInstance()->all());
//dd(app('request')->headers->all());
 $app->register(App\Providers\AppServiceProvider::class);
 $app->register(App\Providers\AuthServiceProvider::class);
 $app->register(App\Providers\EventServiceProvider::class);
$app->register(Prettus\Repository\Providers\RepositoryServiceProvider::class);
$app->register(\Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->register(\App\Providers\LogServiceProvider::class);
$app->register(\Illuminate\Encryption\EncryptionServiceProvider::class);
$app->register(\Maatwebsite\Excel\ExcelServiceProvider::class);
$app->register(Cviebrock\LaravelElasticsearch\ServiceProvider::class);
$app->register(\Illuminate\Mail\MailServiceProvider::class);
$app->register(\Intervention\Image\ImageServiceProvider::class);
$app->register(Melihovv\LaravelLogViewer\LaravelLogViewerServiceProvider::class);
$app->register(Basemkhirat\Elasticsearch\ElasticsearchServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);


if(class_exists('Krlove\EloquentModelGenerator\Provider\GeneratorServiceProvider')){
    $app->register(Krlove\EloquentModelGenerator\Provider\GeneratorServiceProvider::class);
}
/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
