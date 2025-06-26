@var User $user
@var string $title
@var Collection $posts
@var bool $isOwner

@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1>{{ $user->name }}</h1>
                    @if($isOwner)
                        <span class="badge badge-primary">自分のプロフィール</span>
                    @endif
                </div>
                
                <div class="card-body">
                    <div class="profile-info">
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>登録日:</strong> {{ $user->created_at->format('Y年m月d日') }}</p>
                        
                        @if($user->bio)
                            <p><strong>自己紹介:</strong></p>
                            <p>{{ $user->bio }}</p>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h2>投稿 ({{ $posts->count() }}件)</h2>
                
                @if($posts->isNotEmpty())
                    @foreach($posts as $post)
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">{{ $post->title }}</h5>
                                <p class="card-text">{{ Str::limit($post->content, 150) }}</p>
                                <small class="text-muted">{{ $post->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted">まだ投稿がありません。</p>
                @endif
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3>統計</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><strong>投稿数:</strong> {{ $posts->count() }}</li>
                        <li><strong>最新投稿:</strong> 
                            @if($posts->isNotEmpty())
                                {{ $posts->first()->created_at->diffForHumans() }}
                            @else
                                なし
                            @endif
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection