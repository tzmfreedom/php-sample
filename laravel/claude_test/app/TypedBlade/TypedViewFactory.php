<?php

namespace App\TypedBlade;

use App\TypedBlade\Compilers\TypedBladeCompiler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory as FactoryContract;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewFinderInterface;
use InvalidArgumentException;

class TypedViewFactory implements FactoryContract
{
    protected EngineResolver $engines;
    protected ViewFinderInterface $finder;
    protected Dispatcher $events;
    protected Container $container;
    protected array $shared = [];
    protected array $extensions = ['.tblade.php' => 'typed-blade'];
    protected array $composers = [];
    protected array $renderCount = [];

    public function __construct(
        EngineResolver $engines,
        ViewFinderInterface $finder,
        Dispatcher $events,
        Container $container = null
    ) {
        $this->engines = $engines;
        $this->finder = $finder;
        $this->events = $events;
        $this->container = $container;

        $this->share('__env', $this);
    }

    public function file($path, $data = [], $mergeData = []): View
    {
        $data = array_merge($mergeData, $data);

        return new TypedBladeView(
            $this->getEngineFromPath($path),
            $this->normalizeName($path),
            $path,
            $data
        );
    }

    public function make($view, $data = [], $mergeData = []): View
    {
        $path = $this->finder->find(
            $view = $this->normalizeName($view)
        );

        $data = array_merge($mergeData, $this->parseData($data));

        return new TypedBladeView(
            $this->getEngineFromPath($path),
            $view,
            $path,
            array_merge($this->shared, $data)
        );
    }

    protected function normalizeName(string $name): string
    {
        return $name;
    }

    protected function parseData($data): array
    {
        return $data instanceof Arrayable ? $data->toArray() : (array) $data;
    }

    protected function getEngineFromPath(string $path): TypedBladeEngine
    {
        if (!$extension = $this->getExtension($path)) {
            throw new InvalidArgumentException("Unrecognized extension in file: {$path}");
        }

        $engine = $this->engines->resolve($extension);

        if (!$engine instanceof TypedBladeEngine) {
            throw new InvalidArgumentException("Engine must be instance of TypedBladeEngine");
        }

        return $engine;
    }

    protected function getExtension(string $path): ?string
    {
        $extensions = array_keys($this->extensions);

        return Arr::first($extensions, function ($extension) use ($path) {
            return str_ends_with($path, $extension);
        });
    }

    public function first(array $views, $data = [], $mergeData = []): View
    {
        $view = collect($views)->first(function ($view) {
            return $this->exists($view);
        });

        if (!$view) {
            throw new InvalidArgumentException('None of the views in the given array exist.');
        }

        return $this->make($view, $data, $mergeData);
    }

    public function renderWhen($condition, $view, $data = [], $mergeData = []): string
    {
        if ($condition) {
            return $this->make($view, $data, $mergeData)->render();
        }

        return '';
    }

    public function renderUnless($condition, $view, $data = [], $mergeData = []): string
    {
        return $this->renderWhen(!$condition, $view, $data, $mergeData);
    }

    public function renderEach($view, $data, $iterator, $empty = 'raw|'): string
    {
        $result = '';

        if (empty($data)) {
            return str_starts_with($empty, 'raw|') ? substr($empty, 4) : $this->make($empty)->render();
        }

        foreach ($data as $key => $value) {
            $result .= $this->make($view, array_merge($value, [$iterator => $key]))->render();
        }

        return $result;
    }

    public function exists($view): bool
    {
        try {
            $this->finder->find($view);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    public function share($key, $value = null): mixed
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->shared[$key] = $value;
        }

        return $value;
    }

    public function getShared(): array
    {
        return $this->shared;
    }

    public function addNamespace($namespace, $hints): static
    {
        $this->finder->addNamespace($namespace, $hints);

        return $this;
    }

    public function replaceNamespace($namespace, $hints): static
    {
        $this->finder->replaceNamespace($namespace, $hints);

        return $this;
    }

    public function composer($views, $callback): array
    {
        $composers = [];

        foreach ((array) $views as $view) {
            $composers[] = $this->composers[$view] = $callback;
        }

        return $composers;
    }

    public function creator($views, $callback): array
    {
        return $this->composer($views, $callback);
    }

    public function addExtension($extension, $engine): void
    {
        $this->finder->addExtension($extension);

        if (isset($this->extensions[$extension])) {
            unset($this->extensions[$extension]);
        }

        $this->extensions = array_merge([$extension => $engine], $this->extensions);
    }

    public function flushState(): void
    {
        $this->renderCount = [];
    }

    public function flushStateIfDoneRendering(): void
    {
        $this->flushState();
    }

    public function incrementRender(): void
    {
        //
    }

    public function decrementRender(): void
    {
        //
    }

    public function doneRendering(): bool
    {
        return true;
    }

    public function hasRenderedOnce($id): bool
    {
        return isset($this->renderCount[$id]);
    }

    public function markAsRenderedOnce($id): void
    {
        $this->renderCount[$id] = true;
    }
}