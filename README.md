![SharpAPI GitHub cover](https://sharpapi.com/sharpapi-github-laravel-bg.jpg "SharpAPI Laravel Client")

# SharpAPI Laravel Custom Workflow

## 🎯 Laravel wrapper for SharpAPI Custom AI Workflows — build and execute no-code AI API endpoints.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sharpapi/laravel-custom-workflow.svg?style=flat-square)](https://packagist.org/packages/sharpapi/laravel-custom-workflow)
[![Total Downloads](https://img.shields.io/packagist/dt/sharpapi/laravel-custom-workflow.svg?style=flat-square)](https://packagist.org/packages/sharpapi/laravel-custom-workflow)

Check the full documentation on the [Custom AI Workflows](https://sharpapi.com/custom-workflows) page.

---

## Quick Links

| Resource | Link |
|----------|------|
| **Product Details** | [SharpAPI.com](https://sharpapi.com/custom-workflows) |
| **PHP SDK (non-Laravel)** | [sharpapi/php-custom-workflow](https://github.com/sharpapi/php-custom-workflow) |
| **SDK Libraries** | [GitHub - SharpAPI SDKs](https://github.com/sharpapi) |

---

## Requirements

- PHP >= 8.1
- Laravel 10, 11, or 12
- A SharpAPI account with an API key

---

## Installation

### Step 1. Install the package via Composer:

```bash
composer require sharpapi/laravel-custom-workflow
```

### Step 2. Visit [SharpAPI](https://sharpapi.com/) to get your API key.

### Step 3. Add your API key to `.env`:

```env
SHARP_API_KEY=your-api-key
```

### Step 4 (Optional). Publish the configuration:

```bash
php artisan vendor:publish --tag=sharpapi-custom-workflow
```

---

## What it does

This package provides a Laravel-native wrapper around [sharpapi/php-custom-workflow](https://github.com/sharpapi/php-custom-workflow). It adds:

- **Service Provider** with deferred loading (singleton only created when first used)
- **Facade** for quick static access
- **Dependency Injection** support
- **Publishable configuration** with auto-discovery

---

## Usage (Facade)

```php
use SharpAPI\LaravelCustomWorkflow\Facades\CustomWorkflow;

// Execute a workflow
$statusUrl = CustomWorkflow::executeWorkflow('my-sentiment-analyzer', [
    'text' => 'Great product!',
    'score' => 4.5,
]);

// Fetch results when ready
$result = CustomWorkflow::fetchResults($statusUrl);

// List your workflows
$workflows = CustomWorkflow::listWorkflows();

// Describe a workflow's schema
$definition = CustomWorkflow::describeWorkflow('my-analyzer');

// Validate + execute in one call
$statusUrl = CustomWorkflow::validateAndExecute('my-analyzer', ['text' => 'hello']);
```

## Usage (Dependency Injection)

```php
use SharpAPI\CustomWorkflow\CustomWorkflowClient;

class AnalyzeController extends Controller
{
    public function analyze(CustomWorkflowClient $client)
    {
        $statusUrl = $client->executeWorkflow('my-sentiment-analyzer', [
            'text' => 'Great product!',
        ]);

        return $client->fetchResults($statusUrl);
    }
}
```

---

## Configuration

After publishing, the config file is at `config/sharpapi-custom-workflow.php`:

```php
return [
    'api_key' => env('SHARP_API_KEY', env('SHARPAPI_API_KEY')),
    'base_url' => env('SHARPAPI_BASE_URL', 'https://sharpapi.com/api/v1'),
];
```

---

### Do you think our API is missing some obvious functionality?

- [Please let us know via GitHub »](https://github.com/sharpapi/laravel-custom-workflow/issues)

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## Credits

- [A2Z WEB LTD](https://github.com/a2zwebltd)
- [Dawid Makowski](https://github.com/makowskid)
- Boost your [PHP AI](https://sharpapi.com/) capabilities!

---

## License

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE.md)

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---
## Social Media

🚀 For the latest news, tutorials, and case studies, don't forget to follow us on:
- [SharpAPI X (formerly Twitter)](https://x.com/SharpAPI)
- [SharpAPI YouTube](https://www.youtube.com/@SharpAPI)
- [SharpAPI Vimeo](https://vimeo.com/SharpAPI)
- [SharpAPI LinkedIn](https://www.linkedin.com/products/a2z-web-ltd-sharpapicom-automate-with-aipowered-api/)
- [SharpAPI Facebook](https://www.facebook.com/profile.php?id=61554115896974)
