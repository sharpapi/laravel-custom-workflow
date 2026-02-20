<?php

declare(strict_types=1);

namespace SharpAPI\LaravelCustomWorkflow;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use SharpAPI\CustomWorkflow\CustomWorkflowClient;
use SharpAPI\LaravelCustomWorkflow\Exceptions\ApiKeyIsMissing;

/**
 * @internal
 */
final class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sharpapi-custom-workflow.php', 'sharpapi-custom-workflow');

        $this->app->singleton(CustomWorkflowClient::class, static function (): CustomWorkflowClient {
            $apiKey = config('sharpapi-custom-workflow.api_key');

            if (! is_string($apiKey) || $apiKey === '') {
                throw ApiKeyIsMissing::create();
            }

            $baseUrl = config('sharpapi-custom-workflow.base_url');

            return new CustomWorkflowClient($apiKey, $baseUrl);
        });

        $this->app->alias(CustomWorkflowClient::class, 'custom-workflow');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sharpapi-custom-workflow.php' => config_path('sharpapi-custom-workflow.php'),
            ], 'sharpapi-custom-workflow');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            CustomWorkflowClient::class,
            'custom-workflow',
        ];
    }
}
