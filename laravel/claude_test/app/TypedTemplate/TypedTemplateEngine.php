<?php

namespace App\TypedTemplate;

use App\TypedTemplate\Compiler\TemplateCompiler;
use App\TypedTemplate\Exceptions\TypeMismatchException;
use App\TypedTemplate\Exceptions\TemplateNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class TypedTemplateEngine
{
    private TemplateCompiler $compiler;
    private string $templatePath;
    private string $compiledPath;
    private bool $cacheEnabled;

    public function __construct(
        TemplateCompiler $compiler,
        string $templatePath = null,
        string $compiledPath = null,
        bool $cacheEnabled = true
    ) {
        $this->compiler = $compiler;
        $this->templatePath = $templatePath ?? resource_path('views/typed');
        $this->compiledPath = $compiledPath ?? storage_path('framework/views/typed');
        $this->cacheEnabled = $cacheEnabled;
        
        if (!is_dir($this->templatePath)) {
            mkdir($this->templatePath, 0755, true);
        }
        
        if (!is_dir($this->compiledPath)) {
            mkdir($this->compiledPath, 0755, true);
        }
    }

    public function render(string $template, array $data = []): string
    {
        $templateFile = $this->findTemplate($template);
        $compiledFile = $this->getCompiledPath($template);
        
        if ($this->needsCompilation($templateFile, $compiledFile)) {
            $this->compileTemplate($templateFile, $compiledFile, $data);
        }
        
        return $this->renderCompiled($compiledFile, $data);
    }

    public function make(string $template, array $data = []): TypedView
    {
        return new TypedView($this, $template, $data);
    }

    private function findTemplate(string $template): string
    {
        $templateFile = $this->templatePath . '/' . str_replace('.', '/', $template) . '.tpl.php';
        
        if (!file_exists($templateFile)) {
            throw new TemplateNotFoundException("Template '{$template}' not found at {$templateFile}");
        }
        
        return $templateFile;
    }

    private function getCompiledPath(string $template): string
    {
        return $this->compiledPath . '/' . str_replace('.', '_', $template) . '_' . md5($template) . '.php';
    }

    private function needsCompilation(string $templateFile, string $compiledFile): bool
    {
        if (!$this->cacheEnabled) {
            return true;
        }
        
        if (!file_exists($compiledFile)) {
            return true;
        }
        
        return filemtime($templateFile) > filemtime($compiledFile);
    }

    private function compileTemplate(string $templateFile, string $compiledFile, array $data): void
    {
        $templateContent = file_get_contents($templateFile);
        $compiledContent = $this->compiler->compile($templateContent, $data);
        
        file_put_contents($compiledFile, $compiledContent);
    }

    private function renderCompiled(string $compiledFile, array $data): string
    {
        extract($data);
        
        ob_start();
        include $compiledFile;
        return ob_get_clean();
    }

    public function flushCache(): void
    {
        $files = glob($this->compiledPath . '/*.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}