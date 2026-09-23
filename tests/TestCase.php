<?php

declare(strict_types=1);

namespace SharpAPI\LaravelCustomWorkflow\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use SharpAPI\LaravelCustomWorkflow\Facades\CustomWorkflow;
use SharpAPI\LaravelCustomWorkflow\ServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    /**
     * @param  Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return ['CustomWorkflow' => CustomWorkflow::class];
    }
}
