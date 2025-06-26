<?php

namespace App\TypedTemplate;

use Illuminate\Contracts\Support\Renderable;

class TypedView implements Renderable
{
    private TypedTemplateEngine $engine;
    private string $template;
    private array $data;

    public function __construct(TypedTemplateEngine $engine, string $template, array $data = [])
    {
        $this->engine = $engine;
        $this->template = $template;
        $this->data = $data;
    }

    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function render(): string
    {
        return $this->engine->render($this->template, $this->data);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}