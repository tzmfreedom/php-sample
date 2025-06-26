<?php

namespace App\TypedBlade\Compilers;

use App\TypedBlade\TypeValidator;
use App\TypedBlade\Exceptions\TypeMismatchException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\CompilerInterface;

class TypedBladeCompiler extends BladeCompiler implements CompilerInterface
{
    protected TypeValidator $typeValidator;
    protected array $typeDeclarations = [];
    protected array $currentData = [];

    public function __construct(Filesystem $files, string $cachePath, TypeValidator $typeValidator = null)
    {
        parent::__construct($files, $cachePath);
        $this->typeValidator = $typeValidator ?: new TypeValidator();
        
        $this->registerTypedDirectives();
    }

    protected function registerTypedDirectives(): void
    {
        $this->directive('typed', function ($expression) {
            return "<?php \$__typed_data = $expression; ?>";
        });

        $this->directive('var', function ($expression) {
            return $this->compileTypeDeclaration($expression);
        });

        $this->directive('checkTypes', function () {
            return "<?php \$this->validateTypes(\$__typed_data ?? []); ?>";
        });
    }

    public function compile($path = null)
    {
        if ($path) {
            $this->setPath($path);
        }

        if (is_null($this->cachePath)) {
            return;
        }

        $contents = $this->files->get($this->getPath());
        
        $this->extractTypeDeclarations($contents);
        
        $compiled = $this->compileString($contents);
        
        $compiled = $this->wrapWithTypeValidation($compiled);

        if (!is_null($this->cachePath)) {
            $this->files->put(
                $this->getCompiledPath($this->getPath()), $compiled
            );
        }
    }

    protected function extractTypeDeclarations(string &$template): void
    {
        $pattern = '/@var\s+([^\s]+)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/';
        
        preg_match_all($pattern, $template, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $type = $match[1];
            $variable = $match[2];
            $this->typeDeclarations[$variable] = $type;
            
            $template = str_replace($match[0], '', $template);
        }
    }

    protected function compileTypeDeclaration(string $expression): string
    {
        $expression = trim($expression, ' ()');
        
        if (preg_match('/([^\s]+)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/', $expression, $matches)) {
            $type = $matches[1];
            $variable = $matches[2];
            $this->typeDeclarations[$variable] = $type;
            
            return "<?php /* @var {$type} \${$variable} */ ?>";
        }
        
        return "<?php /* Invalid type declaration: {$expression} */ ?>";
    }

    protected function wrapWithTypeValidation(string $compiled): string
    {
        $typeDeclarations = var_export($this->typeDeclarations, true);
        
        $validation = "<?php\n";
        $validation .= "use App\\TypedBlade\\TypeValidator;\n";
        $validation .= "\$__typeValidator = new TypeValidator();\n";
        $validation .= "\$__typeDeclarations = {$typeDeclarations};\n";
        $validation .= "\$__typeValidator->validateTypes(\$__env->getData(), \$__typeDeclarations);\n";
        $validation .= "?>\n";
        
        return $validation . $compiled;
    }

    public function compileString($value)
    {
        $result = '';

        if (strpos($value, '@extends') !== false) {
            $value = $this->compileExtends($value);
        }

        foreach (token_get_all($value) as $token) {
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        }

        if (!empty($this->rawBlocks)) {
            $result = $this->restoreRawContent($result);
        }

        $this->footer = [];

        return $result;
    }

    protected function parseToken($token)
    {
        [$id, $content] = $token;

        if ($id == T_INLINE_HTML) {
            foreach ($this->compilers as $type) {
                $content = $this->{"compile{$type}"}($content);
            }
        }

        return $content;
    }

    public function getTypeDeclarations(): array
    {
        return $this->typeDeclarations;
    }

    public function setCurrentData(array $data): void
    {
        $this->currentData = $data;
    }

    public function validateTypes(array $data): void
    {
        $this->typeValidator->validateTypes($data, $this->typeDeclarations);
    }
}