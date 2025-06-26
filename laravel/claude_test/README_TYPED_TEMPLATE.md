# 型付きPHPテンプレートエンジン

LaravelアプリケーションのためのPHP型チェック機能付きテンプレートエンジンです。

## 特徴

- **型安全性**: テンプレート変数の型を事前に宣言し、実行時に型チェックを行います
- **パフォーマンス**: テンプレートをPHPコードにコンパイルしてキャッシュします
- **Laravel統合**: Laravelのサービスプロバイダーとして簡単に統合できます
- **シンプルな構文**: 直感的なテンプレート構文を提供します

## インストール

1. すでにLaravelプロジェクトに組み込まれています
2. Service Providerは自動的に登録されています

## 基本的な使用方法

### 1. テンプレートファイルの作成

`resources/views/typed/` ディレクトリにテンプレートファイルを作成します。

```php
{* @var string $title *}
{* @var string $message *}
{* @var array $users *}
{* @var int $count *}

<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    
    {% if $count > 0 %}
        <h2>Users ({{ $count }})</h2>
        <ul>
            {% for $users as $user %}
                <li>{{ $user['name'] }} - {{ $user['email'] }}</li>
            {% endfor %}
        </ul>
    {% else %}
        <p>No users found.</p>
    {% endif %}
</body>
</html>
```

### 2. コントローラーでの使用

```php
use App\TypedTemplate\TypedTemplateEngine;

class MyController extends Controller
{
    public function index(TypedTemplateEngine $engine)
    {
        $data = [
            'title' => 'マイページ',
            'message' => 'ようこそ！',
            'users' => [
                ['name' => '田中太郎', 'email' => 'tanaka@example.com'],
                ['name' => '佐藤花子', 'email' => 'sato@example.com'],
            ],
            'count' => 2,
        ];

        return $engine->render('example', $data);
    }
}
```

### 3. ヘルパー関数の使用

```php
// グローバル関数として使用
return typed_template('example', $data);

// Viewオブジェクトとして使用
return typed_view('example', $data)->with('extra', 'value');
```

### 4. Bladeディレクティブの使用

```blade
@typedTemplate('example', ['title' => 'Hello', 'message' => 'World'])
```

## テンプレート構文

### 型宣言

テンプレートの先頭で変数の型を宣言します：

```php
{* @var string $name *}
{* @var int $age *}
{* @var bool $isActive *}
{* @var array $items *}
{* @var User $user *}
{* @var string|null $optionalValue *}
{* @var string[] $stringArray *}
```

### 変数出力

```php
{{ $variableName }}
```

### 条件分岐

```php
{% if $condition %}
    条件が真の場合
{% elseif $anotherCondition %}
    別の条件が真の場合
{% else %}
    どの条件も偽の場合
{% endif %}
```

### ループ

```php
{% for $items as $item %}
    {{ $item }}
{% endfor %}

{% for $items as $key => $value %}
    {{ $key }}: {{ $value }}
{% endfor %}
```

### インクルード

```php
{% include "partial/header" %}
```

## 設定

`config/typed-template.php` で設定をカスタマイズできます：

```php
return [
    'template_path' => resource_path('views/typed'),
    'compiled_path' => storage_path('framework/views/typed'),
    'cache_enabled' => true,
    'auto_escape' => true,
    'strict_types' => true,
];
```

## コマンド

### キャッシュクリア

```bash
php artisan typed-template:clear
```

## エラーハンドリング

型が一致しない場合、`TypeMismatchException` が発生します：

```php
try {
    return $engine->render('template', $data);
} catch (TypeMismatchException $e) {
    // 型エラーの処理
    return response()->json(['error' => $e->getMessage()], 400);
}
```

## テスト

```bash
php artisan test
```

## 実例

### ユーザー一覧ページ

テンプレート（`resources/views/typed/users/index.tpl.php`）：

```php
{* @var string $pageTitle *}
{* @var User[] $users *}
{* @var int $totalCount *}

<h1>{{ $pageTitle }}</h1>

{% if $totalCount > 0 %}
    <p>{{ $totalCount }}人のユーザーが見つかりました。</p>
    
    {% for $users as $user %}
        <div class="user-card">
            <h3>{{ $user->name }}</h3>
            <p>Email: {{ $user->email }}</p>
            <p>登録日: {{ $user->created_at->format('Y年m月d日') }}</p>
        </div>
    {% endfor %}
{% else %}
    <p>ユーザーが見つかりませんでした。</p>
{% endif %}
```

コントローラー：

```php
public function index(TypedTemplateEngine $engine)
{
    $users = User::all();
    
    return $engine->render('users.index', [
        'pageTitle' => 'ユーザー一覧',
        'users' => $users,
        'totalCount' => $users->count(),
    ]);
}
```

## 型サポート

- `string`, `int`, `float`, `bool`, `array`, `object`
- クラス名（例：`User`, `Collection`）
- NULL許可型（例：`string|null`）
- 配列型（例：`string[]`, `User[]`）
- `mixed`型

## パフォーマンス

- テンプレートは初回アクセス時にPHPコードにコンパイルされます
- コンパイル結果はキャッシュされ、ファイル更新時に自動再コンパイルされます
- 型チェックはコンパイル時に行われるため、実行時のオーバーヘッドは最小限です