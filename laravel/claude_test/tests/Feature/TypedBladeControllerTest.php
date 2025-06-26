<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypedBladeControllerTest extends TestCase
{
    public function testUserProfileRoute(): void
    {
        $response = $this->get('/typed-blade/user-profile');

        $response->assertStatus(200);
        $response->assertSee('田中太郎');
        $response->assertSee('tanaka@example.com');
        $response->assertSee('Laravelの新機能について');
    }

    public function testProductListRoute(): void
    {
        $response = $this->get('/typed-blade/products');

        $response->assertStatus(200);
        $response->assertSee('商品一覧');
        $response->assertSee('MacBook Pro');
        $response->assertSee('iPhone 15');
        $response->assertSee('AirPods Pro');
    }

    public function testProductListWithCategoryFilter(): void
    {
        $response = $this->get('/typed-blade/products?category=コンピュータ');

        $response->assertStatus(200);
        $response->assertSee('MacBook Pro');
        $response->assertDontSee('iPhone 15');
    }

    public function testTypeMismatchError(): void
    {
        $response = $this->get('/typed-blade/error');

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error',
            'type'
        ]);
    }
}