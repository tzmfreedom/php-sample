<?php

return [
    'template_path' => env('TYPED_TEMPLATE_PATH', resource_path('views/typed')),
    
    'compiled_path' => env('TYPED_TEMPLATE_COMPILED_PATH', storage_path('framework/views/typed')),
    
    'cache_enabled' => env('TYPED_TEMPLATE_CACHE', true),
    
    'auto_escape' => env('TYPED_TEMPLATE_AUTO_ESCAPE', true),
    
    'strict_types' => env('TYPED_TEMPLATE_STRICT_TYPES', true),
];