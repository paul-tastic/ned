<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Auto-Registration Secret
    |--------------------------------------------------------------------------
    |
    | Shared secret used to authenticate server auto-registration requests.
    | Set via NED_REGISTRATION_SECRET env var. Instances include this in the
    | X-Registration-Secret header when calling POST /api/servers/register.
    |
    | If not set, the registration endpoint is disabled (returns 401).
    |
    */

    'registration_secret' => env('NED_REGISTRATION_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Max Servers Limit
    |--------------------------------------------------------------------------
    |
    | Safety cap on auto-registered servers. Prevents runaway creation from
    | a misconfigured ASG or compromised instance. Registration returns 429
    | when this limit is reached.
    |
    */

    'max_servers' => env('NED_MAX_SERVERS', 50),

    /*
    |--------------------------------------------------------------------------
    | Custom Branding
    |--------------------------------------------------------------------------
    |
    | Override the default Ned branding with your own name and logo.
    | Set NED_BRAND_NAME to display your organization name in the nav.
    | Set NED_BRAND_LOGO to a path (relative to public/) for a custom logo
    | image. When set, the custom logo replaces the default Ned "n" icon
    | and "powered by ned" appears as a subtle secondary badge.
    |
    */

    'brand_name' => env('NED_BRAND_NAME'),
    'brand_logo' => env('NED_BRAND_LOGO'),

];
