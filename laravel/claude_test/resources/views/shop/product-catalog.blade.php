@php
/** @var string $catalogTitle */
/** @var \Illuminate\Support\Collection $products */
/** @var array $categories */
/** @var string|null $selectedCategory */
/** @var int $totalProducts */
/** @var float $minPrice */
/** @var float $maxPrice */
/** @var bool $isLoggedIn */
/** @var \App\Models\User|null $currentUser */
@endphp

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $catalogTitle }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <header class="py-4 bg-primary text-white">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1>{{ $catalogTitle }}</h1>
                        <p class="mb-0">{{ number_format($totalProducts) }}商品をご用意しています</p>
                    </div>
                    <div class="col-md-4 text-end">
                        @if($isLoggedIn && $currentUser)
                            <span>こんにちは、{{ $currentUser->name }}さん</span>
                            <a href="{{ route('profile') }}" class="btn btn-outline-light ms-2">プロフィール</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-light">ログイン</a>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <div class="container my-4">
            <div class="row">
                <!-- サイドバー: フィルター -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h5>商品フィルター</h5>
                        </div>
                        <div class="card-body">
                            <!-- カテゴリフィルター -->
                            <div class="mb-3">
                                <label class="form-label">カテゴリ</label>
                                <select class="form-select" onchange="filterByCategory(this.value)">
                                    <option value="">すべてのカテゴリ</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}" 
                                                {{ $selectedCategory === $category ? 'selected' : '' }}>
                                            {{ $category }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 価格フィルター -->
                            <div class="mb-3">
                                <label class="form-label">価格帯</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" class="form-control" 
                                               placeholder="最低価格" 
                                               min="{{ $minPrice }}" 
                                               max="{{ $maxPrice }}"
                                               value="{{ $minPrice }}">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" 
                                               placeholder="最高価格" 
                                               min="{{ $minPrice }}" 
                                               max="{{ $maxPrice }}"
                                               value="{{ $maxPrice }}">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    ¥{{ number_format($minPrice) }} - ¥{{ number_format($maxPrice) }}
                                </small>
                            </div>

                            <button class="btn btn-primary w-100">フィルター適用</button>
                        </div>
                    </div>
                </div>

                <!-- メインコンテンツ: 商品一覧 -->
                <div class="col-md-9">
                    @if($products->isNotEmpty())
                        <div class="row">
                            @foreach($products as $product)
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card h-100">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" 
                                                 class="card-img-top" 
                                                 alt="{{ $product->name }}"
                                                 style="height: 200px; object-fit: cover;">
                                        @endif
                                        
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title">{{ $product->name }}</h5>
                                            <p class="card-text flex-grow-1">
                                                {{ Str::limit($product->description, 100) }}
                                            </p>
                                            
                                            <div class="mt-auto">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="h5 text-primary mb-0">
                                                        ¥{{ number_format($product->price) }}
                                                    </span>
                                                    <small class="text-muted">{{ $product->category }}</small>
                                                </div>

                                                @if($product->stock > 0)
                                                    <div class="d-flex gap-2">
                                                        @if($isLoggedIn)
                                                            <button class="btn btn-primary flex-grow-1" 
                                                                    onclick="addToCart({{ $product->id }})">
                                                                カートに追加
                                                            </button>
                                                        @else
                                                            <a href="{{ route('login') }}" 
                                                               class="btn btn-outline-primary flex-grow-1">
                                                                ログインして購入
                                                            </a>
                                                        @endif
                                                        <button class="btn btn-outline-secondary" 
                                                                onclick="toggleWishlist({{ $product->id }})">
                                                            ♡
                                                        </button>
                                                    </div>
                                                    <small class="text-success">在庫: {{ $product->stock }}個</small>
                                                @else
                                                    <button class="btn btn-secondary w-100" disabled>
                                                        在庫切れ
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <h3 class="text-muted">商品が見つかりません</h3>
                            <p class="text-muted">検索条件を変更してお試しください。</p>
                            @if($selectedCategory)
                                <a href="{{ route('shop.catalog') }}" class="btn btn-primary">
                                    すべての商品を表示
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterByCategory(category) {
            const url = new URL(window.location);
            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }
            window.location.href = url.toString();
        }

        function addToCart(productId) {
            // AJAX call to add product to cart
            fetch(`/cart/add/${productId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('商品をカートに追加しました');
                }
            });
        }

        function toggleWishlist(productId) {
            // Toggle wishlist functionality
            console.log('Toggle wishlist for product:', productId);
        }
    </script>
</body>
</html>