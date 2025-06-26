<?php

namespace App\TypedTemplate\Compiler;

use App\TypedTemplate\Exceptions\TypeMismatchException;

class TemplateCompiler
{
    private TypeChecker $typeChecker;
    private array $typeDeclarations = [];

    public function __construct()
    {
        $this->typeChecker = new TypeChecker();
    }

    public function compile(string $template, array $data = []): string
    {
        $this->extractTypeDeclarations($template);
        $this->typeChecker->validateTypes($data, $this->typeDeclarations);
        
        $compiled = $this->compileTemplate($template);
        
        return $this->wrapCompiledTemplate($compiled);
    }

    private function extractTypeDeclarations(string &$template): void
    {
        preg_match_all('/\{\*\s*@var\s+(\w+)\s+\$(\w+)\s*\*\}/', $template, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $type = $match[1];
            $variable = $match[2];
            $this->typeDeclarations[$variable] = $type;
            
            $template = str_replace($match[0], '', $template);
        }
    }

    private function compileTemplate(string $template): string
    {
        $template = $this->compileEchoStatements($template);
        $template = $this->compileConditionals($template);
        $template = $this->compileLoops($template);
        $template = $this->compileIncludes($template);
        
        return $template;
    }

    private function compileEchoStatements(string $template): string
    {
        return preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($matches) {
            $expression = trim($matches[1]);
            
            if (str_starts_with($expression, '$')) {
                $variableName = ltrim(explode('[', explode('->', $expression)[0])[0], '$');
                if (isset($this->typeDeclarations[$variableName])) {
                    return "<?php echo htmlspecialchars({$expression} ?? '', ENT_QUOTES, 'UTF-8'); ?>";
                }
            }
            
            return "<?php echo htmlspecialchars({$expression} ?? '', ENT_QUOTES, 'UTF-8'); ?>";
        }, $template);
    }

    private function compileConditionals(string $template): string
    {
        $template = preg_replace('/\{%\s*if\s+(.+?)\s*%\}/', '<?php if ($1): ?>', $template);
        $template = preg_replace('/\{%\s*elseif\s+(.+?)\s*%\}/', '<?php elseif ($1): ?>', $template);
        $template = preg_replace('/\{%\s*else\s*%\}/', '<?php else: ?>', $template);
        $template = preg_replace('/\{%\s*endif\s*%\}/', '<?php endif; ?>', $template);
        
        return $template;
    }

    private function compileLoops(string $template): string
    {
        $template = preg_replace('/\{%\s*for\s+(.+?)\s*%\}/', '<?php foreach ($1): ?>', $template);
        $template = preg_replace('/\{%\s*endfor\s*%\}/', '<?php endforeach; ?>', $template);
        
        return $template;
    }

    private function compileIncludes(string $template): string
    {
        return preg_replace_callback('/\{%\s*include\s+[\'"](.+?)[\'"]\s*%\}/', function ($matches) {
            $includePath = $matches[1];
            return "<?php include resource_path('views/typed/{$includePath}.tpl.php'); ?>";
        }, $template);
    }

    private function wrapCompiledTemplate(string $compiled): string
    {
        return "<?php\n// Compiled typed template\n?>\n" . $compiled;
    }
}