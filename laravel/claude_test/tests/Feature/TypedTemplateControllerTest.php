<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypedTemplateControllerTest extends TestCase
{
    public function testExampleRoute(): void
    {
        $response = $this->get('/typed-template-example');

        $response->assertStatus(200);
        $response->assertSee('Typed Template Example');
        $response->assertSee('John Doe');
        $response->assertSee('jane@example.com');
    }

    public function testTypeMismatchRoute(): void
    {
        $response = $this->get('/typed-template-error');

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error',
            'type'
        ]);
    }
}