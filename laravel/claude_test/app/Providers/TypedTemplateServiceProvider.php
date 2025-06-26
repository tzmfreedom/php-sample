<?php

namespace App\Providers;

use App\TypedTemplate\Compiler\TemplateCompiler;
use App\TypedTemplate\TypedTemplateEngine;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class TypedTemplateServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TemplateCompiler::class, function () {
            return new TemplateCompiler();
        });

        $this->app->singleton(TypedTemplateEngine::class, function ($app) {
            return new TypedTemplateEngine(
                $app->make(TemplateCompiler::class),
                config('typed-template.template_path', resource_path('views/typed')),
                config('typed-template.compiled_path', storage_path('framework/views/typed')),
                config('typed-template.cache_enabled', true)
            );
        });

        $this->app->alias(TypedTemplateEngine::class, 'typed-template');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/typed-template.php' => config_path('typed-template.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\TypedTemplateClearCommand::class,
            ]);
        }

        Blade::directive('typedTemplate', function ($expression) {
            return "<?php echo app('typed-template')->render($expression); ?>";
        });
    }
}