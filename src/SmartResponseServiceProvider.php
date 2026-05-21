<?php

declare(strict_types=1);

namespace Vendor\SmartResponse;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\ServiceProvider;
use Vendor\SmartResponse\Builders\ApiResponseBuilder;
use Vendor\SmartResponse\Builders\WebResponseBuilder;
use Vendor\SmartResponse\Contracts\ApiResponseBuilderInterface;
use Vendor\SmartResponse\Contracts\RequestDetectorInterface;
use Vendor\SmartResponse\Contracts\SmartResponseManagerInterface;
use Vendor\SmartResponse\Contracts\WebResponseBuilderInterface;
use Vendor\SmartResponse\Detectors\RequestTypeDetector;
use Vendor\SmartResponse\Exceptions\Handler\SmartResponseExceptionHandler;
use Vendor\SmartResponse\Formatters\JsonApiFormatter;
use Vendor\SmartResponse\Formatters\XmlApiFormatter;
use Vendor\SmartResponse\Http\Middleware\SmartResponseMiddleware;
use Vendor\SmartResponse\Macros\ResponseMacros;
use Vendor\SmartResponse\Services\SmartResponseManager;
use Vendor\SmartResponse\Support\InertiaAdapter;
use Vendor\SmartResponse\Support\MessageTranslator;
use Vendor\SmartResponse\Support\PaginationTransformer;
use Vendor\SmartResponse\Support\RateLimitResponse;
use Vendor\SmartResponse\Support\ValidationErrorFormatter;

final class SmartResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/smart-response.php', 'smart-response');

        $this->registerBindings();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/smart-response.php' => config_path('smart-response.php'),
            ], 'smart-response-config');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/smart-response'),
            ], 'smart-response-lang');
        }

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'smart-response');

        $this->registerMiddleware();
        ResponseMacros::register();
    }

    private function registerBindings(): void
    {
        $this->app->singleton(RequestDetectorInterface::class, function ($app) {
            return new RequestTypeDetector($app['config']->get('smart-response', []));
        });

        $this->app->singleton(ValidationErrorFormatter::class);
        $this->app->singleton(PaginationTransformer::class);
        $this->app->singleton(InertiaAdapter::class);

        $this->app->singleton(MessageTranslator::class, function ($app) {
            return new MessageTranslator(
                $app->make(Translator::class),
                $app['config']->get('smart-response', []),
            );
        });

        $this->app->singleton(JsonApiFormatter::class, function ($app) {
            return new JsonApiFormatter($app['config']->get('smart-response', []));
        });

        $this->app->singleton(XmlApiFormatter::class, function ($app) {
            return new XmlApiFormatter($app['config']->get('smart-response', []));
        });

        $this->app->singleton(ApiResponseBuilderInterface::class, ApiResponseBuilder::class);
        $this->app->singleton(ApiResponseBuilder::class, function ($app) {
            return new ApiResponseBuilder(
                $app->make(JsonApiFormatter::class),
                $app->make(XmlApiFormatter::class),
                $app['config']->get('smart-response', []),
            );
        });

        $this->app->singleton(WebResponseBuilderInterface::class, WebResponseBuilder::class);
        $this->app->singleton(WebResponseBuilder::class, function ($app) {
            return new WebResponseBuilder(
                $app['url'],
                $app->make(InertiaAdapter::class),
                $app['config']->get('smart-response', []),
            );
        });

        $this->app->singleton(SmartResponseManagerInterface::class, SmartResponseManager::class);
        $this->app->singleton(SmartResponseManager::class, function ($app) {
            $config = $app['config']->get('smart-response', []);

            $cache = ($config['cache']['enabled'] ?? false) && $app->bound('cache')
                ? $app->make('cache')->store($config['cache']['store'] ?? null)
                : null;

            return new SmartResponseManager(
                $app->make(RequestDetectorInterface::class),
                $app->make(ApiResponseBuilder::class),
                $app->make(WebResponseBuilder::class),
                $app->make(PaginationTransformer::class),
                $app->make(ValidationErrorFormatter::class),
                $app->make(MessageTranslator::class),
                $cache instanceof CacheRepository ? $cache : null,
                $app->bound(Dispatcher::class) ? $app->make(Dispatcher::class) : null,
                $config,
            );
        });

        $this->app->singleton(SmartResponseExceptionHandler::class, function ($app) {
            return new SmartResponseExceptionHandler(
                $app->make(SmartResponseManagerInterface::class),
                $app->make(RequestDetectorInterface::class),
                $app['config']->get('smart-response', []),
            );
        });

        $this->app->singleton(RateLimitResponse::class, function ($app) {
            return new RateLimitResponse(
                $app->make(SmartResponseManagerInterface::class),
                $app['config']->get('smart-response', []),
            );
        });
    }

    private function registerMiddleware(): void
    {
        $config = $this->app['config']->get('smart-response.middleware', []);

        if (! ($config['enabled'] ?? true)) {
            return;
        }

        $router = $this->app->make(Registrar::class);
        $alias = $config['alias'] ?? 'smart.response';

        $router->aliasMiddleware($alias, SmartResponseMiddleware::class);
    }
}
