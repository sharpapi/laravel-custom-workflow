<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SharpAPI API Key
    |--------------------------------------------------------------------------
    |
    | Your SharpAPI API key. You can find this in your SharpAPI dashboard
    | at https://sharpapi.com/dashboard
    |
    */

    'api_key' => env('SHARP_API_KEY', env('SHARPAPI_API_KEY')),

    /*
    |--------------------------------------------------------------------------
    | SharpAPI Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the SharpAPI API. You should not need to change this
    | unless you are using a custom or local SharpAPI instance.
    |
    | SHARP_API_BASE_URL is the name used by every other SharpAPI package.
    | SHARPAPI_BASE_URL is still read as a fallback for older installs.
    |
    */

    'base_url' => env('SHARP_API_BASE_URL', env('SHARPAPI_BASE_URL', 'https://sharpapi.com/api/v1')),

];
