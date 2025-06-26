<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

class BladeTestController extends Controller
{
    public function validDashboard(): \Illuminate\Contracts\View\View
    {
        $user = new User([
            'name' => '田中太郎',
            'email' => 'tanaka@example.com',
        ]);
        $user->id = 1;
        $user->created_at = now()->subMonths(3);

        $recentPosts = collect([
            (object) [
                'id' => 1,
                'title' => 'Laravel開発のコツ',
                'content' => 'Laravelでの効率的な開発方法について説明します。',
                'created_at' => now()->subDays(2),
            ],
            (object) [
                'id' => 2,
                'title' => 'PHPStanでの型チェック',
                'content' => 'PHPStanを使った静的解析の活用法をまとめました。',
                'created_at' => now()->subWeek(),
            ],
        ]);

        // ✅ 正しい型の例 - PHPStanエラーなし
        return view('user-dashboard', [
            'user' => $user,                      // \App\Models\User型 ✓
            'pageTitle' => 'ユーザーダッシュボード',  // string型 ✓
            'recentPosts' => $recentPosts,         // \Illuminate\Support\Collection型 ✓
            'totalPosts' => 15,                    // int型 ✓
            'canEdit' => true,                     // bool型 ✓
        ]);
    }

    public function typeMismatchDashboard(): \Illuminate\Contracts\View\View
    {
        // ❌ 型不一致の例 - PHPStanエラーが発生するはず
        return view('user-dashboard', [
            'user' => 'ユーザー名',                    // string型だが\App\Models\User型が期待される ❌
            'pageTitle' => 123,                      // int型だがstring型が期待される ❌
            'recentPosts' => 'no posts',             // string型だが\Illuminate\Support\Collection型が期待される ❌
            'totalPosts' => 'fifteen',               // string型だがint型が期待される ❌
            'canEdit' => 'yes',                      // string型だがbool型が期待される ❌
        ]);
    }

    public function missingDataDashboard(): \Illuminate\Contracts\View\View
    {
        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        
        // ❌ 必須データが不足している例 - PHPStanエラーが発生するはず
        return view('user-dashboard', [
            'user' => $user,
            'pageTitle' => 'ダッシュボード',
            // 'recentPosts' が不足 ❌
            // 'totalPosts' が不足 ❌
            // 'canEdit' が不足 ❌
        ]);
    }

    public function extraDataDashboard(): \Illuminate\Contracts\View\View
    {
        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        
        // ⚠️ 余分なデータがある例 - PHPStan警告が発生するはず
        return view('user-dashboard', [
            'user' => $user,
            'pageTitle' => 'ダッシュボード',
            'recentPosts' => collect([]),
            'totalPosts' => 0,
            'canEdit' => false,
            'extraField' => 'not needed',         // テンプレートで定義されていない ⚠️
            'anotherExtra' => 123,                // テンプレートで定義されていない ⚠️
        ]);
    }

    public function validProductCatalog(Request $request): \Illuminate\Contracts\View\View
    {
        $products = collect([
            (object) [
                'id' => 1,
                'name' => 'MacBook Pro',
                'description' => '高性能ノートパソコン',
                'price' => 298000,
                'stock' => 5,
                'category' => 'コンピュータ',
                'image_url' => 'https://example.com/macbook.jpg',
            ],
            (object) [
                'id' => 2,
                'name' => 'iPhone 15',
                'description' => '最新スマートフォン',
                'price' => 124800,
                'stock' => 0,
                'category' => 'スマートフォン',
                'image_url' => 'https://example.com/iphone.jpg',
            ],
        ]);

        $user = auth()->check() ? auth()->user() : null;

        // ✅ 正しい型の例
        return View::make('shop.product-catalog', [
            'catalogTitle' => '商品カタログ',           // string型 ✓
            'products' => $products,                    // \Illuminate\Support\Collection型 ✓
            'categories' => ['コンピュータ', 'スマートフォン'], // array型 ✓
            'selectedCategory' => $request->get('category'), // string|null型 ✓
            'totalProducts' => 150,                     // int型 ✓
            'minPrice' => 1000.0,                       // float型 ✓
            'maxPrice' => 500000.0,                     // float型 ✓
            'isLoggedIn' => auth()->check(),            // bool型 ✓
            'currentUser' => $user,                     // \App\Models\User|null型 ✓
        ]);
    }

    public function typeMismatchProductCatalog(): \Illuminate\Contracts\View\View
    {
        // ❌ 複数の型不一致例
        return View::make('shop.product-catalog', [
            'catalogTitle' => 123,                      // int型だがstring型が期待される ❌
            'products' => 'no products',                // string型だが\Illuminate\Support\Collection型が期待される ❌
            'categories' => 'Electronics,Books',        // string型だがarray型が期待される ❌
            'selectedCategory' => 123,                  // int型だがstring|null型が期待される ❌
            'totalProducts' => 'one hundred',           // string型だがint型が期待される ❌
            'minPrice' => '1000',                       // string型だがfloat型が期待される ❌
            'maxPrice' => '500000',                     // string型だがfloat型が期待される ❌
            'isLoggedIn' => 'yes',                      // string型だがbool型が期待される ❌
            'currentUser' => 'admin',                   // string型だが\App\Models\User|null型が期待される ❌
        ]);
    }

    public function nullableTypeTest(): \Illuminate\Contracts\View\View
    {
        // ✅ NULL許可型のテスト
        return view('shop.product-catalog', [
            'catalogTitle' => '商品一覧',
            'products' => collect([]),
            'categories' => ['本', 'CD'],
            'selectedCategory' => null,                  // string|null型なのでOK ✓
            'totalProducts' => 0,
            'minPrice' => 0.0,
            'maxPrice' => 10000.0,
            'isLoggedIn' => false,
            'currentUser' => null,                       // \App\Models\User|null型なのでOK ✓
        ]);
    }

    public function wrongNullableTest(): \Illuminate\Contracts\View\View
    {
        // ❌ NULL許可型でない変数にnullを渡す例
        return view('shop.product-catalog', [
            'catalogTitle' => null,                     // string型が期待されるがnullを渡している ❌
            'products' => null,                         // \Illuminate\Support\Collection型が期待されるがnullを渡している ❌
            'categories' => null,                       // array型が期待されるがnullを渡している ❌
            'selectedCategory' => null,                 // OK（string|null型）
            'totalProducts' => null,                    // int型が期待されるがnullを渡している ❌
            'minPrice' => null,                         // float型が期待されるがnullを渡している ❌
            'maxPrice' => null,                         // float型が期待されるがnullを渡している ❌
            'isLoggedIn' => null,                       // bool型が期待されるがnullを渡している ❌
            'currentUser' => null,                      // OK（\App\Models\User|null型）
        ]);
    }

    public function helperFunctionTest(): \Illuminate\Contracts\View\View
    {
        // view() ヘルパー関数での型不一致テスト
        $user = 'not a user object';  // PHPStanは変数の型を追跡する
        
        return view('user-dashboard', [
            'user' => $user,                            // string型だが\App\Models\User型が期待される ❌
            'pageTitle' => 'テスト',
            'recentPosts' => collect([]),
            'totalPosts' => 0,
            'canEdit' => false,
        ]);
    }

    public function dynamicDataTest(int $userId): \Illuminate\Contracts\View\View
    {
        // 動的データの場合
        $userData = $this->getUserDashboardData($userId);
        
        // PHPStanは変数の内容を完全には追跡できないが、
        // 基本的な型チェックは行われる
        return view('user-dashboard', $userData);
    }

    private function getUserDashboardData(int $userId): array
    {
        $user = User::find($userId);
        
        return [
            'user' => $user,
            'pageTitle' => $user ? $user->name . 'のダッシュボード' : 'ユーザーが見つかりません',
            'recentPosts' => $user ? $user->posts()->latest()->take(5)->get() : collect([]),
            'totalPosts' => $user ? $user->posts()->count() : 0,
            'canEdit' => auth()->check() && auth()->id() === $userId,
        ];
    }
}