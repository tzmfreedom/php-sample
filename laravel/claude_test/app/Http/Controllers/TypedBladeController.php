<?php

namespace App\Http\Controllers;

use App\Facades\TypedView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TypedBladeController extends Controller
{
    public function userProfile()
    {
        $user = new User([
            'name' => '田中太郎',
            'email' => 'tanaka@example.com',
            'bio' => 'Webデベロッパーです。Laravelを愛用しています。',
        ]);
        $user->id = 1;
        $user->created_at = now()->subMonths(6);

        $posts = collect([
            (object) [
                'id' => 1,
                'title' => 'Laravelの新機能について',
                'content' => 'Laravel 12の新機能を調べてみました。型付きテンプレートエンジンが特に興味深いです。',
                'created_at' => now()->subDays(3),
            ],
            (object) [
                'id' => 2,
                'title' => 'PHPUnit テストのベストプラクティス',
                'content' => 'テストコードの書き方について学んだことをまとめてみました。',
                'created_at' => now()->subWeek(),
            ],
        ]);

        return TypedView::make('user-profile', [
            'user' => $user,
            'title' => $user->name . 'のプロフィール',
            'posts' => $posts,
            'isOwner' => true,
        ]);
    }

    public function productList(Request $request)
    {
        $products = collect([
            (object) [
                'id' => 1,
                'name' => 'MacBook Pro',
                'description' => '高性能なノートパソコン。プロの開発者に最適です。',
                'price' => 248800,
                'stock' => 5,
                'category' => 'コンピュータ',
                'image_url' => 'https://via.placeholder.com/300x200/007bff/ffffff?text=MacBook+Pro',
            ],
            (object) [
                'id' => 2,
                'name' => 'iPhone 15',
                'description' => '最新のスマートフォン。カメラ性能が大幅に向上しました。',
                'price' => 124800,
                'stock' => 0,
                'category' => 'スマートフォン',
                'image_url' => 'https://via.placeholder.com/300x200/28a745/ffffff?text=iPhone+15',
            ],
            (object) [
                'id' => 3,
                'name' => 'AirPods Pro',
                'description' => 'ノイズキャンセリング機能付きワイヤレスイヤホン。',
                'price' => 32800,
                'stock' => 12,
                'category' => 'オーディオ',
                'image_url' => 'https://via.placeholder.com/300x200/ffc107/ffffff?text=AirPods+Pro',
            ],
        ]);

        $categories = ['コンピュータ', 'スマートフォン', 'オーディオ'];
        $selectedCategory = $request->get('category');

        if ($selectedCategory) {
            $products = $products->filter(function ($product) use ($selectedCategory) {
                return $product->category === $selectedCategory;
            });
        }

        return TypedView::make('product-list', [
            'pageTitle' => '商品一覧',
            'products' => $products,
            'totalCount' => $products->count(),
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function typeMismatchExample()
    {
        try {
            return TypedView::make('user-profile', [
                'user' => 'これは文字列です', // User型が期待されているが文字列を渡す
                'title' => 123, // string型が期待されているが数値を渡す
                'posts' => 'これも文字列です', // Collection型が期待されているが文字列を渡す
                'isOwner' => 'true', // bool型が期待されているが文字列を渡す
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ], 400);
        }
    }
}