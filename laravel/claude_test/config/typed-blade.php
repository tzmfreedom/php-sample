<?php

return [
    'paths' => [
        resource_path('views/typed'),
    ],

    'compiled_path' => env('TYPED_BLADE_COMPILED_PATH', storage_path('framework/views/typed-blade')),

    'cache_enabled' => env('TYPED_BLADE_CACHE', true),

    'strict_types' => env('TYPED_BLADE_STRICT_TYPES', true),

    'auto_escape' => env('TYPED_BLADE_AUTO_ESCAPE', true),

    'type_checking' => [
        'enabled' => env('TYPED_BLADE_TYPE_CHECKING', true),
        
        'ignore_nullable' => env('TYPED_BLADE_IGNORE_NULLABLE', false),
        
        'custom_types' => [
            // 'CustomType' => MyCustomTypeValidator::class,
        ],
    ],

    'aliases' => [
        'Model' => \Illuminate\Database\Eloquent\Model::class,
        'Collection' => \Illuminate\Support\Collection::class,
        'Request' => \Illuminate\Http\Request::class,
        'User' => \App\Models\User::class,
    ],
];