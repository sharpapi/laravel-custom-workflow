<?php

declare(strict_types=1);

namespace SharpAPI\LaravelCustomWorkflow\Exceptions;

use InvalidArgumentException;

/**
 * @internal
 */
final class ApiKeyIsMissing extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     */
    public static function create(): self
    {
        return new self(
            'The SharpAPI API Key is missing. Please publish the [sharpapi-custom-workflow.php] configuration file and set the [api_key].'
        );
    }
}
