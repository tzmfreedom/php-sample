@php
/** @var \App\Models\User $user */
/** @var string $pageTitle */
/** @var \Illuminate\Support\Collection $recentPosts */
/** @var int $totalPosts */
/** @var bool $canEdit */
@endphp

@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>{{ $pageTitle }}</h1>
        <div class="user-info">
            <img src="{{ $user->avatar ?? '/default-avatar.png' }}" alt="Avatar" class="avatar">
            <div class="user-details">
                <h2>{{ $user->name }}</h2>
                <p class="email">{{ $user->email }}</p>
                <small class="member-since">メンバー歴: {{ $user->created_at->diffForHumans() }}</small>
            </div>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>総投稿数</h3>
            <span class="stat-number">{{ number_format($totalPosts) }}</span>
        </div>
        
        <div class="stat-card">
            <h3>最新投稿</h3>
            <span class="stat-number">{{ $recentPosts->count() }}</span>
        </div>
        
        @if($canEdit)
        <div class="stat-card">
            <h3>操作</h3>
            <a href="{{ route('posts.create') }}" class="btn btn-primary">新規投稿</a>
        </div>
        @endif
    </div>

    <div class="recent-posts">
        <h3>最近の投稿</h3>
        
        @if($recentPosts->isNotEmpty())
            @foreach($recentPosts as $post)
                <div class="post-item">
                    <h4>{{ $post->title }}</h4>
                    <p>{{ Str::limit($post->content, 150) }}</p>
                    <div class="post-meta">
                        <span>{{ $post->created_at->format('Y年m月d日') }}</span>
                        @if($canEdit)
                            <a href="{{ route('posts.edit', $post) }}">編集</a>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <p class="no-posts">まだ投稿がありません。</p>
            @if($canEdit)
                <a href="{{ route('posts.create') }}" class="btn btn-secondary">最初の投稿を作成</a>
            @endif
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
}

.post-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
}

.post-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    color: #6c757d;
}
</style>
@endpush