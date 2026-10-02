<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Blade Icons component registration
    |--------------------------------------------------------------------------
    |
    | This app ships its own custom <x-icon> component. Disabling the package's
    | default component registration avoids the name collision that would otherwise
    | trigger the "Svg by name ... from set \"default\" not found" error.
    |
    */

    'components' => [
        'disabled' => true,
    ],
];
