<?php

/*
|--------------------------------------------------------------------------
| Subscription plan
|--------------------------------------------------------------------------
|
| Set in .env:   SUBSCRIPTION_PLAN=basic   or   SUBSCRIPTION_PLAN=pro
|
| A module listed for a plan is available on it; a module missing from the
| current plan is hidden everywhere (menu, Roles page, landing pages, filters)
| and its pages answer 404. Permissions already granted are kept, so moving
| back to a plan that has the module restores access as it was.
|
| After changing .env on the server, run: php artisan config:cache
|
*/

return [

    'current' => strtolower(env('SUBSCRIPTION_PLAN', 'pro')),

    'plans' => [
        'basic' => [
            'label'    => 'Basic',
            'features' => [],
        ],
        'pro' => [
            'label'    => 'Pro',
            'features' => ['budget'],
        ],
    ],

];
