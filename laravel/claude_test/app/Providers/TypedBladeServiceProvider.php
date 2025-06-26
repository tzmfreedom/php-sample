<?php

namespace App\Providers;

use App\TypedBlade\Compilers\TypedBladeCompiler;
use App\TypedBlade\TypedBladeEngine;
use App\TypedBlade\TypedViewFactory;
use App\TypedBlade\TypeValidator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;

class TypedBladeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerTypeValidator();
        $this->registerTypedBladeCompiler();
        $this->registerTypedBladeEngine();
        $this->registerTypedViewFactory();
        $this->registerEngineResolver();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/typed-blade.php' => config_path('typed-blade.php'),
        ], 'config');

        $this->registerBladeDirectives();
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\TypedBladeClearCommand::class,
            ]);
        }
    }

    protected function registerTypeValidator(): void
    {
        $this->app->singleton(TypeValidator::class, function () {
            return new TypeValidator();
        });
    }

    protected function registerTypedBladeCompiler(): void
    {
        $this->app->singleton(TypedBladeCompiler::class, function (Container $app) {
            return new TypedBladeCompiler(
                $app['files'],
                config('typed-blade.compiled_path', storage_path('framework/views/typed-blade')),
                $app[TypeValidator::class]
            );
        });
    }

    protected function registerTypedBladeEngine(): void
    {
        $this->app->singleton(TypedBladeEngine::class, function (Container $app) {
            return new TypedBladeEngine(
                $app[TypedBladeCompiler::class],
                $app['files']
            );
        });
    }

    protected function registerTypedViewFactory(): void
    {
        $this->app->singleton('typed-view', function (Container $app) {
            $resolver = new EngineResolver();
            
            $resolver->register('typed-blade', function () use ($app) {
                return $app[TypedBladeEngine::class];
            });

            $finder = new FileViewFinder(
                $app['files'],
                config('typed-blade.paths', [resource_path('views/typed')])
            );

            $finder->addExtension('tblade.php');

            return new TypedViewFactory(
                $resolver,
                $finder,
                $app[Dispatcher::class],
                $app
            );
        });

        $this->app->alias('typed-view', TypedViewFactory::class);
    }

    protected function registerEngineResolver(): void
    {
        $this->app->extend('view.engine.resolver', function (EngineResolver $resolver, Container $app) {
            $resolver->register('typed-blade', function () use ($app) {
                return $app[TypedBladeEngine::class];
            });

            return $resolver;
        });
    }

    protected function registerBladeDirectives(): void
    {
        if (!$this->app->resolved('blade.compiler')) {
            return;
        }

        $blade = $this->app['blade.compiler'];

        $blade->directive('typedView', function ($expression) {
            return "<?php echo app('typed-view')->make($expression)->render(); ?>";
        });

        $blade->directive('includeTyped', function ($expression) {
            return "<?php echo app('typed-view')->make($expression)->render(); ?>";
        });
    }
}