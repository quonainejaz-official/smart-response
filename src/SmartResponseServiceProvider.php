<?php

declare(strict_types=1);

namespace Quonain\SmartResponse;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\ServiceProvider;
use Quonain\SmartResponse\Builders\ApiResponseBuilder;
use Quonain\SmartResponse\Builders\WebResponseBuilder;
use Quonain\SmartResponse\Contracts\ApiResponseBuilderInterface;
use Quonain\SmartResponse\Contracts\RequestDetectorInterface;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\Contracts\WebResponseBuilderInterface;
use Quonain\SmartResponse\Detectors\RequestTypeDetector;
use Quonain\SmartResponse\Exceptions\Handler\SmartResponseExceptionHandler;
use Quonain\SmartResponse\Formatters\JsonApiFormatter;
use Quonain\SmartResponse\Formatters\XmlApiFormatter;
use Quonain\SmartResponse\Http\Middleware\SmartResponseMiddleware;
use Quonain\SmartResponse\Macros\ResponseMacros;
use Quonain\SmartResponse\Services\SmartResponseManager;
use Quonain\SmartResponse\Support\InertiaAdapter;
use Quonain\SmartResponse\Support\MessageTranslator;
use Quonain\SmartResponse\Support\MetaEnricher;
use Quonain\SmartResponse\Support\PaginationTransformer;
use Quonain\SmartResponse\Support\RateLimitResponse;
use Quonain\SmartResponse\Support\ValidationErrorFormatter;

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
                __DIR__.'/../lang' => $this->app->langPath('quonain/smart-response'),
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
        $this->app->singleton(MetaEnricher::class, function ($app) {
            return new MetaEnricher($app['config']->get('smart-response', []));
        });
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
                $app->make(MetaEnricher::class),
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
