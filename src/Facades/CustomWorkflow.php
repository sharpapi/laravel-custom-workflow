<?php

declare(strict_types=1);

namespace SharpAPI\LaravelCustomWorkflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SharpAPI\CustomWorkflow\DTO\WorkflowListResult listWorkflows(int $page = 1, int $perPage = 15)
 * @method static \SharpAPI\CustomWorkflow\DTO\WorkflowDefinition describeWorkflow(string $slug)
 * @method static string executeWorkflow(string $slug, array $params = [], array $files = [])
 * @method static string validateAndExecute(string $slug, array $params = [], array $files = [])
 * @method static \SharpAPI\Core\Dto\SharpApiJob fetchResults(string $statusUrl)
 * @method static void clearDescribeCache(?string $slug = null)
 */
final class CustomWorkflow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'custom-workflow';
    }
}
