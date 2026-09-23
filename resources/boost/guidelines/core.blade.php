## SharpAPI Custom Workflow (sharpapi/laravel-custom-workflow)

Laravel wrapper for SharpAPI Custom AI Workflows: run your own no-code AI endpoints by slug.

@verbatim
- Call it through the `SharpAPI\LaravelCustomWorkflow\Facades\CustomWorkflow` facade (or inject `SharpAPI\CustomWorkflow\CustomWorkflowClient`). Env: `SHARP_API_KEY`, optional `SHARP_API_BASE_URL`.
- `$files` is `[fieldName => localPath]`: readable local file paths only. Storage disk keys and `UploadedFile` objects do not work; pass `Storage::disk('local')->path($key)`.
- Prefer `validateAndExecute()` and catch `SharpAPI\CustomWorkflow\Exceptions\ValidationException`; `getErrors()` returns `[field => [messages]]`.
- `fetchResults()` blocks while it polls (up to 180 s by default). Run it in a queued job whose `$timeout` is above that wait, and check `$job->status === 'failed'`: a failed job does not throw.
- A missing API key throws `ApiKeyIsMissing` when the client is first resolved, not at boot.
@endverbatim

For integration details, use the `sharpapi-custom-workflow` skill.
