<?php

namespace App\TypedBlade;

use App\TypedBlade\Compilers\TypedBladeCompiler;
use App\TypedBlade\Exceptions\TypedViewException;
use Illuminate\Contracts\View\Engine;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\CompilerInterface;

class TypedBladeEngine implements Engine
{
    protected TypedBladeCompiler $compiler;
    protected Filesystem $files;
    protected string $cachePath;

    public function __construct(TypedBladeCompiler $compiler, Filesystem $files = null)
    {
        $this->compiler = $compiler;
        $this->files = $files ?: new Filesystem;
        $this->cachePath = config('view.compiled');
    }

    public function get($path, array $data = [])
    {
        $this->lastCompiled[] = $path;

        if ($this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        $compiledPath = $this->compiler->getCompiledPath($path);

        $results = $this->evaluatePath($compiledPath, $data);

        array_pop($this->lastCompiled);

        return $results;
    }

    protected function evaluatePath(string $path, array $data)
    {
        $obLevel = ob_get_level();

        ob_start();

        extract($data, EXTR_SKIP);

        try {
            include $path;
        } catch (Exception $e) {
            $this->handleViewException($e, $obLevel);
        } catch (Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    protected function handleViewException(Throwable $e, int $obLevel)
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }

    public function getCompiler(): TypedBladeCompiler
    {
        return $this->compiler;
    }

    protected array $lastCompiled = [];
}