@var string $pageTitle
@var Collection $products
@var int $totalCount
@var array $categories
@var string|null $selectedCategory

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">{{ $pageTitle }}</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <p class="text-muted">{{ number_format($totalCount) }}個の商品が見つかりました</p>
            </div>
            
            <div class="col-md-6">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        カテゴリ: {{ $selectedCategory ?? '全て' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?category=">全て</a></li>
                        @foreach($categories as $category)
                            <li>
                                <a class="dropdown-item @if($selectedCategory === $category) active @endif" 
                                   href="?category={{ urlencode($category) }}">
                                    {{ $category }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        
        @if($products->isNotEmpty())
            <div class="row">
                @foreach($products as $product)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" class="card-img-top" alt="{{ $product->name }}" style="height: 200px; object-fit: cover;">
                            @endif
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $product->name }}</h5>
                                <p class="card-text flex-grow-1">{{ Str::limit($product->description, 100) }}</p>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h5 mb-0 text-primary">¥{{ number_format($product->price) }}</span>
                                        
                                        @if($product->stock > 0)
                                            <span class="badge bg-success">在庫あり</span>
                                        @else
                                            <span class="badge bg-danger">在庫切れ</span>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-2">
                                        <small class="text-muted">カテゴリ: {{ $product->category }}</small>
                                    </div>
                                    
                                    @if($product->stock > 0)
                                        <button class="btn btn-primary w-100 mt-2">カートに追加</button>
                                    @else
                                        <button class="btn btn-secondary w-100 mt-2" disabled>在庫切れ</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <h3 class="text-muted">商品が見つかりませんでした</h3>
                <p class="text-muted">検索条件を変更してお試しください。</p>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>