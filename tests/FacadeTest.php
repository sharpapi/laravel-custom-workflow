<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Http;
use SharpAPI\Core\DTO\SharpApiJob;
use SharpAPI\CustomWorkflow\CustomWorkflowClient;
use SharpAPI\CustomWorkflow\DTO\WorkflowDefinition;
use SharpAPI\CustomWorkflow\Exceptions\ValidationException;
use SharpAPI\LaravelCustomWorkflow\Facades\CustomWorkflow;

beforeEach(function () {
    config()->set('sharpapi-custom-workflow.api_key', 'test-key');
});

it('resolves the facade to the client singleton', function () {
    expect(CustomWorkflow::getFacadeRoot())->toBe(app(CustomWorkflowClient::class));
});

it('can be faked at the facade', function () {
    CustomWorkflow::shouldReceive('validateAndExecute')
        ->once()
        ->with('my-sentiment-analyzer', ['text' => 'Great product!'], [])
        ->andReturn('https://sharpapi.com/api/v1/job/status/job-1');

    CustomWorkflow::shouldReceive('fetchResults')
        ->once()
        ->andReturn(new SharpApiJob(
            id: 'job-1',
            type: 'custom_workflow',
            status: 'success',
            result: (object) ['sentiment' => 'positive'],
        ));

    $statusUrl = CustomWorkflow::validateAndExecute('my-sentiment-analyzer', ['text' => 'Great product!'], []);
    $job = CustomWorkflow::fetchResults($statusUrl);

    expect(json_decode((string) $job->getResultJson(), true))->toBe(['sentiment' => 'positive']);
});

it('shares one mock between the class binding and the facade', function () {
    $job = new SharpApiJob('job-1', 'custom_workflow', 'failed', null);

    $this->mock(CustomWorkflowClient::class, fn ($mock) => $mock->shouldReceive('fetchResults')->andReturn($job));

    expect(CustomWorkflow::fetchResults('https://example.test/status'))->toBe($job)
        ->and(app('custom-workflow')->fetchResults('https://example.test/status')->status)->toBe('failed');
});

it('validates payloads with the SDK definition factory', function () {
    $definition = WorkflowDefinition::fromArray([
        'slug' => 'x',
        'name' => 'X',
        'input_mode' => 'application/json',
        'params' => [['key' => 'text', 'type' => 'json_string', 'required' => true]],
    ]);

    try {
        $definition->validate(['extra' => 1]);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->getErrors())->toBe([
            'text' => ['Field is required'],
            'extra' => ['Unknown parameter'],
        ]);
    }
});

it('is not intercepted by Http::fake because the SDK uses its own Guzzle client', function () {
    Http::fake();
    config()->set('sharpapi-custom-workflow.base_url', 'http://127.0.0.1:1/api/v1');

    expect(fn () => CustomWorkflow::listWorkflows())->toThrow(ConnectException::class);

    Http::assertNothingSent();
});
