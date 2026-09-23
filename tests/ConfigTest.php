<?php

declare(strict_types=1);

use SharpAPI\CustomWorkflow\CustomWorkflowClient;
use SharpAPI\LaravelCustomWorkflow\Exceptions\ApiKeyIsMissing;

/**
 * Evaluate the package config file with the given environment variables set.
 *
 * @param  array<string, string>  $env
 * @return array<string, mixed>
 */
function customWorkflowConfigWith(array $env): array
{
    $names = ['SHARP_API_KEY', 'SHARPAPI_API_KEY', 'SHARP_API_BASE_URL', 'SHARPAPI_BASE_URL'];
    $backup = [];

    foreach ($names as $name) {
        $backup[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null, getenv($name)];
        unset($_ENV[$name], $_SERVER[$name]);
        putenv($name);
    }

    foreach ($env as $name => $value) {
        $_ENV[$name] = $_SERVER[$name] = $value;
        putenv("{$name}={$value}");
    }

    try {
        return require __DIR__.'/../config/sharpapi-custom-workflow.php';
    } finally {
        foreach ($backup as $name => [$envValue, $serverValue, $getenvValue]) {
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);
            if ($envValue !== null) {
                $_ENV[$name] = $envValue;
            }
            if ($serverValue !== null) {
                $_SERVER[$name] = $serverValue;
            }
            if ($getenvValue !== false) {
                putenv("{$name}={$getenvValue}");
            }
        }
    }
}

it('defaults the base url to the public SharpAPI endpoint', function () {
    expect(customWorkflowConfigWith([])['base_url'])->toBe('https://sharpapi.com/api/v1');
});

it('reads SHARP_API_BASE_URL like every other SharpAPI package', function () {
    expect(customWorkflowConfigWith(['SHARP_API_BASE_URL' => 'https://mock.test/api/v1'])['base_url'])
        ->toBe('https://mock.test/api/v1');
});

it('still accepts the legacy SHARPAPI_BASE_URL name', function () {
    expect(customWorkflowConfigWith(['SHARPAPI_BASE_URL' => 'https://legacy.test/api/v1'])['base_url'])
        ->toBe('https://legacy.test/api/v1');
});

it('prefers SHARP_API_BASE_URL when both names are set', function () {
    expect(customWorkflowConfigWith([
        'SHARP_API_BASE_URL' => 'https://new.test/api/v1',
        'SHARPAPI_BASE_URL' => 'https://legacy.test/api/v1',
    ])['base_url'])->toBe('https://new.test/api/v1');
});

it('falls back to SHARPAPI_API_KEY for the api key', function () {
    expect(customWorkflowConfigWith(['SHARPAPI_API_KEY' => 'legacy-key'])['api_key'])->toBe('legacy-key')
        ->and(customWorkflowConfigWith(['SHARP_API_KEY' => 'new-key', 'SHARPAPI_API_KEY' => 'legacy-key'])['api_key'])->toBe('new-key');
});

it('builds the client from config', function () {
    config()->set('sharpapi-custom-workflow.api_key', 'test-key');
    config()->set('sharpapi-custom-workflow.base_url', 'https://mock.test/api/v1');

    $client = app('custom-workflow');

    expect($client)->toBeInstanceOf(CustomWorkflowClient::class)
        ->and($client)->toBe(app(CustomWorkflowClient::class))
        ->and($client->getApiBaseUrl())->toBe('https://mock.test/api/v1');
});

it('throws ApiKeyIsMissing only when the client is resolved', function () {
    config()->set('sharpapi-custom-workflow.api_key', null);

    expect(fn () => app(CustomWorkflowClient::class))->toThrow(ApiKeyIsMissing::class);
});
