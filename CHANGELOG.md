# Changelog

All notable changes to `sharpapi/laravel-custom-workflow` will be documented in this file.

## 1.1.0 - 2026-09-23

- The base URL now reads `SHARP_API_BASE_URL`, the env name used by every other SharpAPI package. The old `SHARPAPI_BASE_URL` still works as a fallback.
- Fixed the facade docblock: `fetchResults()` returns `SharpAPI\Core\DTO\SharpApiJob` (the namespace was misspelled `Dto`).
- Added a Pest + Orchestra Testbench test suite.
- Ships Laravel Boost resources: a core guideline (`resources/boost/guidelines/core.blade.php`) and the `sharpapi-custom-workflow` skill.

## 1.0.0 - 2026-02-20

- Initial release
- Laravel service provider with deferred loading
- Facade for `CustomWorkflowClient`
- Publishable configuration
- Auto-discovery support for Laravel 10/11/12
