# 型付きBladeテンプレートエンジン

LaravelのBladeテンプレートエンジンをベースにした型安全なテンプレートエンジンです。従来のBladeの機能をすべて保持しながら、変数の型チェック機能を追加しています。

## 特徴

- **Bladeコンパチブル**: 既存のBlade構文をすべてサポート
- **型安全性**: テンプレート変数の型を事前宣言し、実行時に型チェック
- **パフォーマンス**: Bladeの高速コンパイル機能を継承
- **Laravel統合**: Laravelのビューシステムに完全統合
- **高度な型サポート**: Union型、Generic型、クラス型をサポート

## インストールと設定

### 1. 自動設定
Service Providerは既に登録済みです。

### 2. 設定ファイル（オプション）
```bash
php artisan vendor:publish --tag=config
```

## 基本的な使用方法

### 1. テンプレートファイルの作成

ファイル拡張子は `.tblade.php` を使用し、`resources/views/typed/` ディレクトリに配置します。

```blade
@var User $user
@var string $title
@var Collection $posts
@var bool $isOwner

@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="container">
    <h1>{{ $user->name }}</h1>
    
    @if($isOwner)
        <span class="badge badge-primary">オーナー</span>
    @endif
    
    <p>Email: {{ $user->email }}</p>
    <p>登録日: {{ $user->created_at->format('Y年m月d日') }}</p>
    
    <h2>投稿一覧</h2>
    @if($posts->isNotEmpty())
        @foreach($posts as $post)
            <div class="card">
                <h3>{{ $post->title }}</h3>
                <p>{{ $post->content }}</p>
            </div>
        @endforeach
    @else
        <p>投稿がありません。</p>
    @endif
</div>
@endsection
```

### 2. コントローラーでの使用

```php
use App\Facades\TypedView;

class UserController extends Controller
{
    public function show(User $user)
    {
        $posts = $user->posts()->latest()->get();
        
        return TypedView::make('user-profile', [
            'user' => $user,
            'title' => $user->name . 'のプロフィール',
            'posts' => $posts,
            'isOwner' => auth()->user()->is($user),
        ]);
    }
}
```

### 3. ヘルパー関数の使用

```php
// ビューオブジェクトを作成
$view = typed_blade('user-profile', ['user' => $user, 'title' => 'Profile']);

// 直接レンダリング
$html = render_typed_blade('user-profile', ['user' => $user, 'title' => 'Profile']);
```

### 4. 通常のBladeテンプレートから型付きBladeを呼び出し

```blade
@typedView('user-profile', ['user' => $user, 'title' => 'Profile'])

@includeTyped('components.user-card', ['user' => $user])
```

## 型宣言の構文

### 基本型

```blade
@var string $name
@var int $age
@var float $price
@var bool $isActive
@var array $items
@var object $config
```

### NULL許可型

```blade
@var string|null $description
@var ?int $optionalId
```

### Union型

```blade
@var string|int $value
@var User|Admin $account
```

### 配列型

```blade
@var string[] $tags
@var User[] $users
@var int[] $numbers
```

### クラス型

```blade
@var User $user
@var Collection $posts
@var Request $request
@var Model $model
```

### Generic型

```blade
@var Collection<User> $users
@var Collection<string> $tags
```

### 設定ファイルでのエイリアス

```php
// config/typed-blade.php
'aliases' => [
    'User' => App\Models\User::class,
    'Post' => App\Models\Post::class,
],
```

## 型チェックとエラーハンドリング

### 型不一致エラー

```php
try {
    return TypedView::make('user-profile', [
        'user' => 'invalid string', // User型が期待されている
        'title' => 123,            // string型が期待されている
    ]);
} catch (TypeMismatchException $e) {
    // エラーハンドリング
    return response()->json(['error' => $e->getMessage()], 400);
}
```

### エラーメッセージ例

```
Type mismatch for variable '$user': expected User, got string
Type mismatch for variable '$title': expected string, got integer
```

## 設定

`config/typed-blade.php` で詳細設定が可能です：

```php
return [
    // テンプレートパス
    'paths' => [
        resource_path('views/typed'),
    ],

    // コンパイル済みファイルの保存先
    'compiled_path' => storage_path('framework/views/typed-blade'),

    // キャッシュ有効/無効
    'cache_enabled' => true,

    // 厳密な型チェック
    'strict_types' => true,

    // 自動エスケープ
    'auto_escape' => true,

    // 型チェック設定
    'type_checking' => [
        'enabled' => true,
        'ignore_nullable' => false,
    ],

    // 型エイリアス
    'aliases' => [
        'User' => App\Models\User::class,
        'Collection' => Illuminate\Support\Collection::class,
    ],
];
```

## コマンド

### キャッシュクリア

```bash
php artisan typed-blade:clear
```

## 実例

### 商品一覧ページ

```blade
@var string $pageTitle
@var Collection $products
@var int $totalCount
@var array $categories
@var string|null $selectedCategory

<!DOCTYPE html>
<html>
<head>
    <title>{{ $pageTitle }}</title>
</head>
<body>
    <h1>{{ $pageTitle }}</h1>
    <p>{{ number_format($totalCount) }}個の商品</p>
    
    <select name="category">
        <option value="">全カテゴリ</option>
        @foreach($categories as $category)
            <option value="{{ $category }}" 
                    @if($selectedCategory === $category) selected @endif>
                {{ $category }}
            </option>
        @endforeach
    </select>
    
    @if($products->isNotEmpty())
        <div class="products">
            @foreach($products as $product)
                <div class="product-card">
                    <h3>{{ $product->name }}</h3>
                    <p>¥{{ number_format($product->price) }}</p>
                    <p>{{ $product->description }}</p>
                    
                    @if($product->stock > 0)
                        <button>カートに追加</button>
                    @else
                        <button disabled>在庫切れ</button>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p>商品が見つかりませんでした。</p>
    @endif
</body>
</html>
```

### コントローラー

```php
public function index(Request $request)
{
    $products = Product::query();
    
    if ($category = $request->get('category')) {
        $products->where('category', $category);
    }
    
    $products = $products->get();
    $categories = Product::distinct('category')->pluck('category')->toArray();
    
    return TypedView::make('product-list', [
        'pageTitle' => '商品一覧',
        'products' => $products,
        'totalCount' => $products->count(),
        'categories' => $categories,
        'selectedCategory' => $request->get('category'),
    ]);
}
```

## 従来のBladeとの違い

| 項目 | 従来のBlade | TypedBlade |
|------|-------------|------------|
| ファイル拡張子 | `.blade.php` | `.tblade.php` |
| 型宣言 | なし | `@var Type $variable` |
| 型チェック | なし | 実行時に自動チェック |
| エラー検出 | 実行時エラー | 型不一致で事前検出 |
| パフォーマンス | 高速 | 高速（型チェックオーバーヘッド最小） |
| 互換性 | - | Blade構文100%互換 |

## ベストプラクティス

1. **型宣言は必須**: すべてのテンプレート変数に型を宣言
2. **NULL許可型の活用**: オプショナルな値には `|null` を追加
3. **エラーハンドリング**: 型不一致エラーを適切にキャッチ
4. **設定の活用**: プロジェクトに応じて型エイリアスを設定

## トラブルシューティング

### よくあるエラー

1. **型不一致エラー**
   - 宣言した型と実際のデータが一致しない
   - NULL許可型が必要な場合は `|null` を追加

2. **クラスが見つからない**
   - 型エイリアスの設定を確認
   - 完全なクラス名（FQCN）を使用

3. **テンプレートが見つからない**
   - ファイル拡張子が `.tblade.php` になっているか確認
   - `resources/views/typed/` ディレクトリに配置されているか確認

## パフォーマンス

- 型チェックはコンパイル時に組み込まれるため、実行時のオーバーヘッドは最小限
- Bladeの高速コンパイル機能をそのまま活用
- キャッシュシステムにより、再コンパイルは必要時のみ実行