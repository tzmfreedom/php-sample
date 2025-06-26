<?php

namespace App\Http\Controllers;

use App\Facades\TypedView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PHPStanTestController extends Controller
{
    public function validExample(): \Illuminate\Contracts\View\View
    {
        $user = new User(['name' => 'John', 'email' => 'john@example.com']);
        $posts = collect([
            (object) ['title' => 'Post 1', 'content' => 'Content 1'],
            (object) ['title' => 'Post 2', 'content' => 'Content 2'],
        ]);

        // 正しい型の例 - PHPStanエラーなし
        return TypedView::make('user-profile', [
            'user' => $user,           // User型 ✓
            'title' => 'User Profile', // string型 ✓
            'posts' => $posts,         // Collection型 ✓
            'isOwner' => true,         // bool型 ✓
        ]);
    }

    public function typeMismatchExample(): \Illuminate\Contracts\View\View
    {
        // 型不一致の例 - PHPStanエラーが発生するはず
        return TypedView::make('user-profile', [
            'user' => 'not a user object',    // string型だがUser型が期待される ❌
            'title' => 123,                   // int型だがstring型が期待される ❌
            'posts' => 'not a collection',    // string型だがCollection型が期待される ❌
            'isOwner' => 'true',              // string型だがbool型が期待される ❌
        ]);
    }

    public function missingDataExample(): \Illuminate\Contracts\View\View
    {
        // 必須データが不足している例 - PHPStanエラーが発生するはず
        return TypedView::make('user-profile', [
            'user' => new User(['name' => 'John']),
            // 'title' が不足 ❌
            // 'posts' が不足 ❌
            // 'isOwner' が不足 ❌
        ]);
    }

    public function extraDataExample(): \Illuminate\Contracts\View\View
    {
        $user = new User(['name' => 'John', 'email' => 'john@example.com']);
        
        // 余分なデータがある例 - PHPStan警告が発生するはず
        return TypedView::make('user-profile', [
            'user' => $user,
            'title' => 'User Profile',
            'posts' => collect([]),
            'isOwner' => true,
            'extraField' => 'not needed',      // テンプレートで定義されていない ⚠️
            'anotherExtra' => 123,             // テンプレートで定義されていない ⚠️
        ]);
    }

    public function helperFunctionExample(): string
    {
        // ヘルパー関数使用例
        
        // 正しい例
        $validResult = typed_blade('product-list', [
            'pageTitle' => 'Products',
            'products' => collect([]),
            'totalCount' => 0,
            'categories' => ['Electronics', 'Books'],
            'selectedCategory' => null,
        ]);

        // 型不一致例 - PHPStanエラーが発生するはず
        $invalidResult = render_typed_blade('product-list', [
            'pageTitle' => 123,                    // int型だがstring型が期待される ❌
            'products' => 'not a collection',      // string型だがCollection型が期待される ❌
            'totalCount' => 'zero',                // string型だがint型が期待される ❌
            'categories' => 'not an array',        // string型だがarray型が期待される ❌
            'selectedCategory' => 123,             // int型だがstring|null型が期待される ❌
        ]);

        return $validResult;
    }

    public function dynamicDataExample(Request $request): \Illuminate\Contracts\View\View
    {
        // 動的データの例 - 変数を使用
        $userData = $this->getUserData($request->input('user_id'));
        
        // PHPStanは変数の内容を完全には追跡できないが、
        // 基本的な型チェックは行われる
        return TypedView::make('user-profile', $userData);
    }

    private function getUserData(int $userId): array
    {
        $user = User::find($userId);
        
        return [
            'user' => $user,
            'title' => $user ? $user->name . "'s Profile" : 'User Not Found',
            'posts' => $user ? $user->posts : collect([]),
            'isOwner' => auth()->check() && auth()->id() === $userId,
        ];
    }

    public function nullableTypeExample(): \Illuminate\Contracts\View\View
    {
        // NULL許可型のテスト
        return TypedView::make('product-list', [
            'pageTitle' => 'Products',
            'products' => collect([]),
            'totalCount' => 10,
            'categories' => ['Electronics'],
            'selectedCategory' => null,  // string|null型なのでOK ✓
        ]);
    }

    public function wrongNullableExample(): \Illuminate\Contracts\View\View
    {
        // NULL許可型でない変数にnullを渡す例
        return TypedView::make('product-list', [
            'pageTitle' => null,  // string型が期待されるがnullを渡している ❌
            'products' => collect([]),
            'totalCount' => 10,
            'categories' => ['Electronics'],
            'selectedCategory' => null,
        ]);
    }
}