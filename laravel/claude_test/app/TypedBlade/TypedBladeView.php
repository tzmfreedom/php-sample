<?php

namespace App\TypedBlade;

use App\TypedBlade\Exceptions\TypeMismatchException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Engine;
use Illuminate\Contracts\View\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class TypedBladeView implements View
{
    protected TypedBladeEngine $engine;
    protected string $view;
    protected array $data;
    protected string $path;

    public function __construct(TypedBladeEngine $engine, string $view, string $path, array $data = [])
    {
        $this->engine = $engine;
        $this->view = $view;
        $this->path = $path;
        $this->data = $data;
    }

    public function name(): string
    {
        return $this->view;
    }

    public function with($key, $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function render(): string
    {
        try {
            $contents = $this->renderContents();

            $response = isset($callback) ? $callback($this, $contents) : null;

            return !is_null($response) ? $response : $contents;
        } catch (Exception $e) {
            $this->flushStateIfDoneRendering();

            throw $e;
        } catch (Throwable $e) {
            $this->flushStateIfDoneRendering();

            throw $e;
        }
    }

    protected function renderContents(): string
    {
        return $this->engine->get($this->path, $this->gatherData());
    }

    public function gatherData(): array
    {
        $data = array_merge($this->data, [
            '__env' => app('view'),
            'app' => app(),
            'errors' => app('view')->shared('errors', new ViewErrorBag),
        ]);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    protected function flushStateIfDoneRendering(): void
    {
        //
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        $this->with($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function offsetGet($key): mixed
    {
        return $this->data[$key];
    }

    public function offsetSet($key, $value): void
    {
        $this->with($key, $value);
    }

    public function offsetUnset($key): void
    {
        unset($this->data[$key]);
    }

    public function getEngine(): Engine
    {
        return $this->engine;
    }
}