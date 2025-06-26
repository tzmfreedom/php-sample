# PHPStan Blade 型チェック

PHPStanを使用してBladeテンプレート（TypedBladeと通常のBlade両方）の型安全性を事前にチェックする機能です。

## 概要

この機能により、コンパイル時に以下のチェックが可能になります：

- **型不一致の検出**: コントローラーから渡されるデータの型とテンプレートで期待される型の不一致
- **必須データの不足**: テンプレートで宣言されているが提供されていない変数
- **未使用データの警告**: テンプレートで使用されていない余分な変数
- **NULL許可型の検証**: nullable型の適切な使用
- **制御構文の型チェック**: @if, @foreach, @while等の制御構文での型安全性検証

## サポートするテンプレート形式

### 1. TypedBlade (.tblade.php)
専用構文を使用した型宣言：

```blade
@var User $user
@var string $title
@var Collection $posts
```

### 2. 通常のBlade (.blade.php)
PHPDocブロックを使用した型宣言：

```blade
@php
/** @var \App\Models\User $user */
/** @var string $title */
/** @var \Illuminate\Support\Collection $posts */
@endphp
```

## インストールと設定

### 1. PHPStanのインストール
```bash
composer require --dev phpstan/phpstan larastan/larastan phpstan/extension-installer
```

### 2. 設定ファイル

#### phpstan.neon
```yaml
includes:
    - ./vendor/larastan/larastan/extension.neon
    - ./phpstan-typed-blade.neon

parameters:
    level: 8
    paths:
        - app
        - resources/views/typed
```

#### phpstan-typed-blade.neon
```yaml
services:
    - App\PHPStan\TypedBladeTemplateParser
    
    typedViewDataRule:
        class: App\PHPStan\Rules\TypedViewDataRule
        tags:
            - phpstan.rules.rule
    
    typedViewControllerDataRule:
        class: App\PHPStan\Rules\TypedViewControllerDataRule
        arguments:
            - @reflectionProvider
        tags:
            - phpstan.rules.rule
```

## 使用方法

### 基本的な実行
```bash
./vendor/bin/phpstan analyse app/ --level=8
```

### 特定のファイルのみ
```bash
./vendor/bin/phpstan analyse app/Http/Controllers/UserController.php
```

### テンプレートも含めて
```bash
./vendor/bin/phpstan analyse app/ resources/views/typed/
```

## チェック対象

### 1. TypedView::make() 呼び出し (.tblade.php)
```php
// ✅ 正しい例
return TypedView::make('user-profile', [
    'user' => $user,           // User型
    'title' => 'Profile',      // string型
    'posts' => collect([]),    // Collection型
    'isOwner' => true,         // bool型
]);

// ❌ 型不一致
return TypedView::make('user-profile', [
    'user' => 'not a user',    // string型（User型が期待される）
    'title' => 123,            // int型（string型が期待される）
]);
```

### 2. view() 呼び出し (.blade.php)
```php
// ✅ 正しい例
return view('user-dashboard', [
    'user' => $user,              // \App\Models\User型
    'pageTitle' => 'Dashboard',   // string型
    'recentPosts' => collect([]), // \Illuminate\Support\Collection型
    'totalPosts' => 10,           // int型
    'canEdit' => true,            // bool型
]);

// ❌ 型不一致
return view('user-dashboard', [
    'user' => 'username',         // string型（\App\Models\User型が期待される）
    'pageTitle' => 123,           // int型（string型が期待される）
]);
```

### 3. View::make() 呼び出し (.blade.php)
```php
use Illuminate\Support\Facades\View;

// ✅ 正しい例
return View::make('shop.product-catalog', [
    'catalogTitle' => 'Products',
    'products' => collect([]),
    'categories' => ['Electronics', 'Books'],
]);
```

### 4. ヘルパー関数 (.tblade.php)
```php
// typed_blade()
$view = typed_blade('template-name', $data);

// render_typed_blade()
$html = render_typed_blade('template-name', $data);
```

## エラータイプと解決方法

### 型不一致エラー
```
Template "user-profile" expects variable "$user" of type "User", but "string" provided.
```

**解決方法:**
```php
// ❌ 間違い
'user' => 'john'

// ✅ 修正
'user' => User::find(1)
```

### 必須データ不足エラー
```
Template "user-profile" expects variable "$title" of type "string", but it was not provided.
```

**解決方法:**
```php
return TypedView::make('user-profile', [
    'user' => $user,
    'title' => 'Profile Page',  // 追加
    'posts' => collect([]),
    'isOwner' => false,
]);
```

### 未使用データ警告
```
Template "user-profile" does not expect variable "$extraData", but it was provided.
```

**解決方法:**
```php
// オプション1: 余分なデータを削除
return TypedView::make('user-profile', [
    'user' => $user,
    // 'extraData' => $data,  // 削除
]);

// オプション2: テンプレートに型宣言を追加
// @var mixed $extraData
```

## 型システムの詳細

### サポートされる型

#### TypedBlade (.tblade.php) での型宣言
```blade
@var string $name
@var int $count
@var float $price
@var bool $active
@var array $items
@var User $user
@var Collection $posts
@var string|null $description
@var ?User $user
@var Collection<User> $users
```

#### 通常のBlade (.blade.php) での型宣言
```blade
@php
/** @var string $name */
/** @var int $count */
/** @var float $price */
/** @var bool $active */
/** @var array $items */
/** @var \App\Models\User $user */
/** @var \Illuminate\Support\Collection $posts */
/** @var string|null $description */
/** @var \App\Models\User|null $user */
/** @var \Illuminate\Support\Collection<\App\Models\User> $users */
@endphp
```

#### 基本型
- `string`, `int`, `float`, `bool`, `array`

#### クラス型
- 完全なクラス名（FQCN）を推奨: `\App\Models\User`
- 型エイリアスも使用可能: `User`（設定で定義）

#### NULL許可型
- `string|null` または `?string`
- `\App\Models\User|null` または `?\App\Models\User`

#### Union型
- `string|int`
- `\App\Models\User|\App\Models\Admin`

#### Generic型（実験的）
- `\Illuminate\Support\Collection<\App\Models\User>`
- `array<string>`

### 型エイリアス

設定ファイルで型エイリアスを定義可能：

```yaml
# phpstan-typed-blade.neon
parameters:
    typedBlade:
        typeAliases:
            User: 'App\Models\User'
            Collection: 'Illuminate\Support\Collection'
```

## テスト実行

### 1. TypedBlade テスト用コントローラー
`PHPStanTestController` に意図的な型エラーを含むメソッドを作成済み：

```php
public function typeMismatchExample()
{
    return TypedView::make('user-profile', [
        'user' => 'not a user object',    // 型エラー
        'title' => 123,                   // 型エラー
    ]);
}
```

### 2. 通常のBlade テスト用コントローラー
`BladeTestController` に意図的な型エラーを含むメソッドを作成済み：

```php
public function typeMismatchDashboard()
{
    return view('user-dashboard', [
        'user' => 'ユーザー名',                    // string型だが\App\Models\User型が期待される
        'pageTitle' => 123,                      // int型だがstring型が期待される
        'recentPosts' => 'no posts',             // string型だが\Illuminate\Support\Collection型が期待される
    ]);
}
```

### 3. テスト実行スクリプト
```bash
php run-phpstan-test.php
```

### 4. 手動テスト
```bash
# TypedBlade テスト
./vendor/bin/phpstan analyse app/Http/Controllers/PHPStanTestController.php

# 通常のBlade テスト
./vendor/bin/phpstan analyse app/Http/Controllers/BladeTestController.php

# すべてのコントローラーをテスト
./vendor/bin/phpstan analyse app/Http/Controllers/

# 特定のテンプレートを含めてテスト
./vendor/bin/phpstan analyse app/Http/Controllers/ resources/views/
```

## 期待される出力例

### TypedBlade エラー例
```
------ ----------------------------------------------------------------
 Line   app/Http/Controllers/PHPStanTestController.php
------ ----------------------------------------------------------------
 25     Template "user-profile" expects variable "$user" of type 
        "App\Models\User", but "string" provided.
 26     Template "user-profile" expects variable "$title" of type 
        "string", but "int" provided.
 27     Template "user-profile" expects variable "$posts" of type 
        "Illuminate\Support\Collection", but "string" provided.
 28     Template "user-profile" expects variable "$isOwner" of type 
        "bool", but "string" provided.
------ ----------------------------------------------------------------
```

### 通常のBlade エラー例
```
------ ----------------------------------------------------------------
 Line   app/Http/Controllers/BladeTestController.php
------ ----------------------------------------------------------------
 28     Blade template "user-dashboard" expects variable "$user" of type 
        "\App\Models\User", but "string" provided.
 29     Blade template "user-dashboard" expects variable "$pageTitle" of type 
        "string", but "int" provided.
 30     Blade template "user-dashboard" expects variable "$recentPosts" of type 
        "\Illuminate\Support\Collection", but "string" provided.
 45     Controller method provides unused variable "$extraField" to Blade 
        template "user-dashboard".
------ ----------------------------------------------------------------
```

## カスタマイズ

### 1. チェック対象の拡張

独自の関数やメソッドを追加：

```yaml
# phpstan-typed-blade.neon
parameters:
    # TypedBlade用
    typedBlade:
        checkCalls:
            functionCalls:
                - 'my_custom_typed_view_function'
            staticCalls:
                - 'MyClass::customMake'
    
    # 通常のBlade用
    blade:
        checkCalls:
            functionCalls:
                - 'my_custom_view_function'
            staticCalls:
                - 'MyViewClass::customMake'
```

### 2. 新しいルールの追加

`App\PHPStan\Rules` ディレクトリに新しいルールクラスを作成し、設定ファイルに登録。

### 3. カスタム型バリデーター

複雑な型チェックロジックを `TypedBladeTemplateParser` に追加。

## トラブルシューティング

### よくあるエラー

1. **"Template not found"**
   - テンプレートファイルのパスを確認
   - `.tblade.php` 拡張子を確認

2. **"Rule not applied"**
   - `phpstan-typed-blade.neon` が正しく読み込まれているか確認
   - サービス登録を確認

3. **"Type not recognized"**
   - 型エイリアスの設定を確認
   - 完全なクラス名（FQCN）を使用

### デバッグ

```bash
# より詳細な出力
./vendor/bin/phpstan analyse --debug

# 特定のルールのみ実行（カスタム設定が必要）
./vendor/bin/phpstan analyse --configuration=phpstan-debug.neon
```

## パフォーマンス

- テンプレートの型情報はキャッシュされます
- 大規模プロジェクトでは `--memory-limit` オプションが必要な場合があります

```bash
./vendor/bin/phpstan analyse --memory-limit=1G
```

## CI/CD統合

### GitHub Actions例

```yaml
name: PHPStan TypedBlade Check

on: [push, pull_request]

jobs:
  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install dependencies
        run: composer install --no-progress --no-suggest --prefer-dist --optimize-autoloader
      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse --error-format=github
```

## Blade制御構文の型チェック

### 対応する制御構文

この機能は以下のBlade制御構文の型安全性をチェックします：

#### 1. @if文の条件式チェック
```blade
{{-- ✅ 正しい例 --}}
@if($canEdit)  {{-- bool型 --}}
    <p>編集可能</p>
@endif

{{-- ❌ 型エラー --}}
@if($title)    {{-- string型をboolean条件で使用 --}}
    <p>タイトルあり</p>
@endif
```

#### 2. @foreach文のイテレータチェック
```blade
{{-- ✅ 正しい例 --}}
@foreach($posts as $post)  {{-- Collection型 --}}
    <div>{{ $post->title }}</div>
@endforeach

{{-- ❌ 型エラー --}}
@foreach($title as $char)  {{-- string型をiterableとして使用 --}}
    <span>{{ $char }}</span>
@endforeach
```

#### 3. @forelse文のイテレータチェック
```blade
{{-- ✅ 正しい例 --}}
@forelse($posts as $post)  {{-- Collection型 --}}
    <div>{{ $post->title }}</div>
@empty
    <p>投稿なし</p>
@endforelse

{{-- ❌ 型エラー --}}
@forelse($canEdit as $item)  {{-- bool型をiterableとして使用 --}}
    <span>{{ $item }}</span>
@empty
    <p>データなし</p>
@endforelse
```

#### 4. @while文の条件式チェック
```blade
{{-- ✅ 正しい例 --}}
@while($canEdit && $postCount > 0)  {{-- bool型とint型の適切な使用 --}}
    <p>条件が真</p>
    @break
@endwhile

{{-- ❌ 型エラー --}}
@while($posts)  {{-- Collection型をboolean条件で使用 --}}
    <p>実行される</p>
    @break
@endwhile
```

#### 5. @switch文の変数チェック
```blade
{{-- ✅ 正しい例 --}}
@switch($category)  {{-- string|null型 --}}
    @case('tech')
        <p>技術</p>
        @break
    @default
        <p>その他</p>
@endswitch

{{-- ❌ 型エラー --}}
@switch($posts)  {{-- Collection型をswitchで使用 --}}
    @case('empty')
        <p>空</p>
        @break
@endswitch
```

#### 6. @isset/@empty文（常に許可）
```blade
{{-- ✅ 任意の型で使用可能 --}}
@isset($category)   {{-- string|null型 --}}
    <p>カテゴリ: {{ $category }}</p>
@endisset

@empty($posts)      {{-- Collection型 --}}
    <p>投稿なし</p>
@endempty
```

### 制御構文エラー例

#### 未宣言変数エラー
```
Blade template "control-structure-test" (line 45): Variable $undeclaredVariable is not declared in template in if statement
```

#### 型不一致エラー
```
Blade template "control-structure-test" (line 23): Expected boolean expression, but variable is of type 'string' in if statement
Blade template "control-structure-test" (line 35): Expected iterable type for foreach, but variable is of type 'string' in foreach statement
```

### テストファイル

制御構文の型チェック機能をテストするためのファイルが用意されています：

#### テンプレート
- `resources/views/control-structure-test.blade.php`
  - 正しい制御構文の使用例
  - 型不一致の制御構文例
  - 未宣言変数の使用例

#### コントローラー
- `app/Http/Controllers/ControlStructureTestController.php`
  - `validControlStructures()`: 正しい型でのテンプレート呼び出し
  - `invalidControlStructures()`: 型不一致でのテンプレート呼び出し
  - `mixedControlStructures()`: 一部正しく一部間違った型
  - `nullableTest()`: NULL許可型のテスト
  - `missingVariables()`: 必須変数不足のテスト

### テスト実行方法

```bash
# 制御構文テストコントローラーの解析
./vendor/bin/phpstan analyse app/Http/Controllers/ControlStructureTestController.php

# 期待されるエラー出力例
------ ----------------------------------------------------------------
 Line   app/Http/Controllers/ControlStructureTestController.php
------ ----------------------------------------------------------------
 47     Blade template "control-structure-test" (line 23): Expected 
        boolean expression, but variable is of type 'string' in if statement
 48     Blade template "control-structure-test" (line 35): Expected 
        iterable type for foreach, but variable is of type 'string' in foreach statement
 49     Blade template "control-structure-test" (line 42): Expected 
        iterable type for forelse, but variable is of type 'bool' in forelse statement
------ ----------------------------------------------------------------
```

### 設定

`phpstan-typed-blade.neon`で制御構文チェックを制御できます：

```yaml
parameters:
    blade:
        # 制御構文の型チェック
        validateControlStructures: true
        
        # チェック対象の制御構文
        controlStructureChecks:
            - 'if'          # @if文の条件式
            - 'foreach'     # @foreach文のイテレータ
            - 'forelse'     # @forelse文のイテレータ
            - 'while'       # @while文の条件式
            - 'switch'      # @switch文の変数
            - 'isset'       # @isset文の変数（常に許可）
            - 'empty'       # @empty文の変数（常に許可）
```

## まとめ

この機能により、以下のBladeテンプレート形式で型安全性を確保できます：

### 対応テンプレート形式

| 形式 | ファイル拡張子 | 型宣言方法 | 呼び出し方法 |
|------|-------------|-----------|-------------|
| **TypedBlade** | `.tblade.php` | `@var User $user` | `TypedView::make()` |
| **通常のBlade** | `.blade.php` | `@php /** @var \App\Models\User $user */ @endphp` | `view()`, `View::make()` |

### 機能一覧

✅ **型不一致の検出**: コンパイル時に型エラーを検出  
✅ **必須データ不足の検出**: 必要な変数の漏れを検出  
✅ **未使用データの警告**: 余分な変数の警告  
✅ **NULL許可型サポート**: nullable型の適切な検証  
✅ **Union型サポート**: 複数型の組み合わせ  
✅ **Generic型サポート**: Collection<User>などの高度な型  
✅ **制御構文型チェック**: @if, @foreach, @while等の型安全性検証  
✅ **コントローラー統合**: Controller内での自動チェック  
✅ **CI/CD統合**: GitHub Actionsでの自動実行  

### 開発フロー

1. **型宣言**: テンプレートで変数の型を宣言
2. **開発**: コントローラーでデータを渡す
3. **チェック**: PHPStanで型安全性を確認（制御構文含む）
4. **修正**: 型エラーがあれば修正
5. **コミット**: 型安全なコードをコミット

これにより、従来のBladeテンプレートとTypedBladeテンプレートの両方で、制御構文を含む包括的な型安全性を確保し、実行前に型エラーを検出し、より安全で保守性の高いLaravelアプリケーションを開発できます。