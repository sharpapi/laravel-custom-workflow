---
name: sharpapi-custom-workflow
description: Call SharpAPI Custom AI Workflows from Laravel with sharpapi/laravel-custom-workflow (CustomWorkflow facade, CustomWorkflowClient, listWorkflows/describeWorkflow/executeWorkflow/validateAndExecute/fetchResults, SharpApiJob, ValidationException, config/sharpapi-custom-workflow.php). Use when executing a workflow by slug, uploading files to one, polling for its result in a queued job, handling its errors, or faking it in tests.
---

# SharpAPI Custom Workflow

## When to use this skill

- Running a SharpAPI custom workflow (a no-code AI endpoint identified by its slug) from a controller, job or command.
- Sending files to a form-data workflow.
- Moving `fetchResults()` polling into a queue, or tuning its wait.
- Handling validation, API and timeout errors.
- Writing tests for code that calls the facade.

Namespaces: the Laravel wrapper is `SharpAPI\LaravelCustomWorkflow\`; the SDK it wraps (`sharpapi/php-custom-workflow`) is `SharpAPI\CustomWorkflow\`; the job DTO comes from `sharpapi/php-core` as `SharpAPI\Core\DTO\SharpApiJob`.

## Install / wiring checklist

1. `composer require sharpapi/laravel-custom-workflow`. The service provider and the `CustomWorkflow` alias are auto-discovered.
2. `.env`:
   ```dotenv
   SHARP_API_KEY=your-api-key            # SHARPAPI_API_KEY is read as a fallback
   SHARP_API_BASE_URL=                   # optional; SHARPAPI_BASE_URL is read as a fallback
   ```
3. Optional: `php artisan vendor:publish --tag=sharpapi-custom-workflow` publishes `config/sharpapi-custom-workflow.php`.
4. Run a queue worker if you poll results in jobs (recommended, see Recipes).

The provider is deferred. It binds `CustomWorkflowClient` as a **singleton** (alias `custom-workflow`), built from `sharpapi-custom-workflow.api_key` and `sharpapi-custom-workflow.base_url`. An empty key throws `SharpAPI\LaravelCustomWorkflow\Exceptions\ApiKeyIsMissing` (an `InvalidArgumentException`) on first resolution, so `config:cache` and boot never fail on a missing key; the first call does.

## API & config reference

Facade `SharpAPI\LaravelCustomWorkflow\Facades\CustomWorkflow` proxies every public method of `SharpAPI\CustomWorkflow\CustomWorkflowClient`:

| Method | Returns | Notes |
|---|---|---|
| `listWorkflows(int $page = 1, int $perPage = 15)` | `DTO\WorkflowListResult` | `GET /custom`; `$perPage` is capped at 100. |
| `describeWorkflow(string $slug)` | `DTO\WorkflowDefinition` | `GET /custom/{slug}`; cached in memory on the client instance. |
| `clearDescribeCache(?string $slug = null)` | `void` | Clears one slug, or all with `null`. |
| `executeWorkflow(string $slug, array $params = [], array $files = [])` | `string` status URL | `POST /custom/{slug}`. JSON body when `$files` is empty, multipart otherwise. No client-side validation. |
| `validateAndExecute(string $slug, array $params = [], array $files = [])` | `string` status URL | `describeWorkflow()` + `$definition->validate()` + `executeWorkflow()`. |
| `fetchResults(string $statusUrl)` | `SharpAPI\Core\DTO\SharpApiJob` | Blocks and polls until the status is `success` or `failed`. |
| `setApiJobStatusPollingWait(int $seconds)` | `void` | Max total wait, default 180. |
| `setApiJobStatusPollingInterval(int $seconds)` / `setUseCustomInterval(bool)` | `void` | Default 10. The API's `Retry-After` header wins unless `setUseCustomInterval(true)`. |

DTOs (all `public readonly` except `SharpApiJob`):

- `WorkflowListResult`: `workflows` (list of `WorkflowDefinition`), `total`, `perPage`, `currentPage`, `totalPages`, `count()`, `isEmpty()`.
- `WorkflowDefinition`: `slug`, `name`, `description`, `inputMode` (`Enums\InputMode::JSON` or `FORM_DATA`), `outputSchema` (`?array`), `isActive`, `endpoint`, `createdAt`, `updatedAt`, `params` (list of `WorkflowParam`), `requiredParams()`, `optionalParams()`, `validate(array $params = [], array $files = [])`, `toArray()`.
- `WorkflowParam`: `key`, `label`, `type` (`Enums\ParamType`: `json_string`, `json_number`, `json_boolean`, `json_object`, `json_array`, `form_data_text`, `form_data_file`), `required`, `defaultValue`.
- `SharpApiJob`: `id`, `type`, `status` (`'success'` or `'failed'` after polling), `result` (`?stdClass`), `toArray()`, `getResultJson()`, `getResultArray()` (a shallow cast, nested values may still be `stdClass`), `getResultObject()`.

Config `config/sharpapi-custom-workflow.php`:

```php
'api_key'  => env('SHARP_API_KEY', env('SHARPAPI_API_KEY')),
'base_url' => env('SHARP_API_BASE_URL', env('SHARPAPI_BASE_URL', 'https://sharpapi.com/api/v1')),
```

Polling wait and interval are not in config; set them on the client (see Recipes).

## Recipes

### Execute with validation (JSON workflow)

```php
use SharpAPI\CustomWorkflow\Exceptions\ValidationException;
use SharpAPI\LaravelCustomWorkflow\Facades\CustomWorkflow;

try {
    $statusUrl = CustomWorkflow::validateAndExecute('my-sentiment-analyzer', [
        'text' => 'Great product!',
        'score' => 4.5,
    ]);
} catch (ValidationException $e) {
    return back()->withErrors($e->getErrors()); // ['score' => ['Must be a number'], ...]
}

ProcessWorkflowResult::dispatch($statusUrl);
```

Client-side rules (`PayloadValidator`): JSON mode checks required keys and types and rejects unknown keys (`'Unknown parameter'`). `json_object` must be an associative array, `json_array` a list. Form-data mode checks required text fields and that every required file path is readable. It does not reject extra fields.

### Upload files (form-data workflow)

`$files` maps the workflow's param key to a **local path**; the SDK reads it with `file_get_contents()` and sends `basename()` as the filename.

```php
$key = $request->file('document')->store('workflow-uploads', 'local');

$statusUrl = CustomWorkflow::validateAndExecute('document-analyzer',
    ['language' => 'English'],
    ['document' => Storage::disk('local')->path($key)],
);
```

For S3 or another remote disk, copy the file to a local temp path first. Do not pass an `UploadedFile` object or a disk key. The request's temp file is gone by the time a queued job runs.

### Poll in a queued job

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use SharpAPI\LaravelCustomWorkflow\Facades\CustomWorkflow;

class ProcessWorkflowResult implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 240; // above the 180 s default polling wait
    public int $tries = 3;

    public function __construct(public string $statusUrl) {}

    public function handle(): void
    {
        $job = CustomWorkflow::fetchResults($this->statusUrl);

        if ($job->status === 'failed') {
            throw new RuntimeException("SharpAPI workflow job {$job->id} failed.");
        }

        $data = json_decode((string) $job->getResultJson(), true) ?? []; // deep array
        // ... persist $data
    }
}
```

Keep the queue connection's `retry_after` above `$timeout`, or the job gets picked up twice. Re-running `fetchResults()` on the same status URL is safe: it only reads the job status.

### Change the polling wait

```php
CustomWorkflow::setApiJobStatusPollingWait(300); // seconds; raise the job $timeout to match
```

The client is a singleton, so this applies to every later call in the same process (a whole queue worker).

### Discover workflows and their params

```php
$page = CustomWorkflow::listWorkflows(1, 50);

foreach ($page->workflows as $workflow) {
    $workflow->slug;
    $workflow->inputMode->isFormData();
    array_map(fn ($p) => $p->key, $workflow->requiredParams());
}
```

## Gotchas

- **`fetchResults()` blocks.** It sleeps between polls until the job finishes or the wait (180 s by default) runs out, then throws `SharpAPI\Core\Exceptions\ApiException('Polling timed out ...')`. Never call it in a web request.
- **A failed job does not throw.** `fetchResults()` returns a `SharpApiJob` with `status === 'failed'`; always check it. With `sharpapi/php-core` 1.4.0, a failed job whose `result` is `null` can instead raise a `TypeError` inside the SDK, so a queued job should let any `Throwable` fail it rather than swallowing only `ApiException`.
- **Use `json_decode($job->getResultJson(), true)` for arrays.** `getResultArray()` only casts the top level, so nested objects can stay `stdClass`.
- **Catch `ValidationException` by name.** It and `ApiKeyIsMissing` both extend `InvalidArgumentException`, so a broad catch hides a missing key.
- **`describeWorkflow()` is cached per client instance**, and the client is a singleton. A long-running queue worker keeps the old schema after a workflow's params change; call `CustomWorkflow::clearDescribeCache($slug)` or restart the workers.
- HTTP errors other than 429 surface as Guzzle exceptions (`GuzzleHttp\Exception\ClientException` for 4xx). A 429 is retried (3 attempts in total by default) and then throws `ApiException` with code 429.
- `executeWorkflow()` skips client-side validation. Use it only when the payload is already known to be valid.

## Testing

The SDK sends requests through its own Guzzle client, **not** Laravel's HTTP client, so `Http::fake()` does not intercept it. Fake at the facade instead:

```php
use SharpAPI\Core\DTO\SharpApiJob;
use SharpAPI\CustomWorkflow\Exceptions\ValidationException;
use SharpAPI\LaravelCustomWorkflow\Facades\CustomWorkflow;

it('stores the workflow result', function () {
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

    // ... run the code under test
});

it('surfaces validation errors', function () {
    CustomWorkflow::shouldReceive('validateAndExecute')
        ->andThrow(new ValidationException(['text' => ['Field is required']]));

    // ... assert the errors reach the user
});
```

- Build definitions with the SDK's own factory: `WorkflowDefinition::fromArray(['slug' => 'x', 'name' => 'X', 'input_mode' => 'application/json', 'params' => [['key' => 'text', 'type' => 'json_string', 'required' => true]]])`.
- Test the failed path with `status: 'failed'`.
- For code that type-hints `CustomWorkflowClient`, bind a mock: `$this->mock(CustomWorkflowClient::class, fn ($m) => $m->shouldReceive('fetchResults')->andReturn($job));`. The facade and the `custom-workflow` alias resolve to the same binding.
- Set `config(['sharpapi-custom-workflow.api_key' => 'test-key'])` in tests that resolve the real client.
