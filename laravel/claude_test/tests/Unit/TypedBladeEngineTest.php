<?php

namespace Tests\Unit;

use App\TypedBlade\Compilers\TypedBladeCompiler;
use App\TypedBlade\TypedBladeEngine;
use App\TypedBlade\TypeValidator;
use App\TypedBlade\Exceptions\TypeMismatchException;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class TypedBladeEngineTest extends TestCase
{
    private TypedBladeEngine $engine;
    private string $testPath;
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testPath = sys_get_temp_dir() . '/typed_blade_test';
        $this->cachePath = sys_get_temp_dir() . '/typed_blade_cache';

        if (!is_dir($this->testPath)) {
            mkdir($this->testPath, 0755, true);
        }
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }

        $files = new Filesystem();
        $compiler = new TypedBladeCompiler($files, $this->cachePath, new TypeValidator());
        $this->engine = new TypedBladeEngine($compiler, $files);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    private function cleanupTestFiles(): void
    {
        $paths = [$this->testPath, $this->cachePath];
        
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($path);
            }
        }
    }

    public function testSimpleTypedBladeTemplate(): void
    {
        $template = '@var string $name
Hello {{ $name }}!';
        
        $templateFile = $this->testPath . '/simple.tblade.php';
        file_put_contents($templateFile, $template);

        $result = $this->engine->get($templateFile, ['name' => 'World']);
        
        $this->assertStringContainsString('Hello World!', $result);
    }

    public function testTypeValidation(): void
    {
        $template = '@var int $count
Count: {{ $count }}';
        
        $templateFile = $this->testPath . '/count.tblade.php';
        file_put_contents($templateFile, $template);

        $this->expectException(TypeMismatchException::class);
        $this->engine->get($templateFile, ['count' => 'not a number']);
    }

    public function testValidTypesPass(): void
    {
        $template = '@var string $title
@var int $count
@var bool $active

<h1>{{ $title }}</h1>
<p>Count: {{ $count }}</p>
@if($active)
    <p>Active</p>
@endif';
        
        $templateFile = $this->testPath . '/valid.tblade.php';
        file_put_contents($templateFile, $template);

        $result = $this->engine->get($templateFile, [
            'title' => 'Test Title',
            'count' => 42,
            'active' => true,
        ]);
        
        $this->assertStringContainsString('Test Title', $result);
        $this->assertStringContainsString('Count: 42', $result);
        $this->assertStringContainsString('Active', $result);
    }
}