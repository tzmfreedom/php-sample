<?php

namespace Tests\Unit;

use App\TypedTemplate\TypedTemplateEngine;
use App\TypedTemplate\Compiler\TemplateCompiler;
use App\TypedTemplate\Exceptions\TypeMismatchException;
use App\TypedTemplate\Exceptions\TemplateNotFoundException;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TypedTemplateEngineTest extends TestCase
{
    private TypedTemplateEngine $engine;
    private string $testTemplatePath;
    private string $testCompiledPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testTemplatePath = sys_get_temp_dir() . '/typed_templates_test';
        $this->testCompiledPath = sys_get_temp_dir() . '/typed_compiled_test';

        $this->engine = new TypedTemplateEngine(
            new TemplateCompiler(),
            $this->testTemplatePath,
            $this->testCompiledPath,
            false
        );

        if (!is_dir($this->testTemplatePath)) {
            mkdir($this->testTemplatePath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    private function cleanupTestFiles(): void
    {
        $paths = [$this->testTemplatePath, $this->testCompiledPath];
        
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*');
                foreach ($files as $file) {
                    unlink($file);
                }
                rmdir($path);
            }
        }
    }

    public function testSimpleTemplateRendering(): void
    {
        $templateContent = '{* @var string $name *}Hello {{ $name }}!';
        $templateFile = $this->testTemplatePath . '/simple.tpl.php';
        file_put_contents($templateFile, $templateContent);

        $result = $this->engine->render('simple', ['name' => 'World']);
        
        $this->assertStringContainsString('Hello World!', $result);
    }

    public function testTypeValidation(): void
    {
        $templateContent = '{* @var int $count *}Count: {{ $count }}';
        $templateFile = $this->testTemplatePath . '/count.tpl.php';
        file_put_contents($templateFile, $templateContent);

        $this->expectException(TypeMismatchException::class);
        $this->engine->render('count', ['count' => 'not a number']);
    }

    public function testTemplateNotFound(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        $this->engine->render('nonexistent', []);
    }

    public function testConditionalRendering(): void
    {
        $templateContent = '{* @var bool $show *}{% if $show %}Visible{% else %}Hidden{% endif %}';
        $templateFile = $this->testTemplatePath . '/conditional.tpl.php';
        file_put_contents($templateFile, $templateContent);

        $resultTrue = $this->engine->render('conditional', ['show' => true]);
        $resultFalse = $this->engine->render('conditional', ['show' => false]);

        $this->assertStringContainsString('Visible', $resultTrue);
        $this->assertStringContainsString('Hidden', $resultFalse);
    }

    public function testLoopRendering(): void
    {
        $templateContent = '{* @var array $items *}{% for $items as $item %}{{ $item }} {% endfor %}';
        $templateFile = $this->testTemplatePath . '/loop.tpl.php';
        file_put_contents($templateFile, $templateContent);

        $result = $this->engine->render('loop', ['items' => ['A', 'B', 'C']]);
        
        $this->assertStringContainsString('A B C', $result);
    }
}