@extends('layouts.app')

@section('title', 'History: '.$article->title)

@section('content')
    <section class="prose">
        <h1>History: {{ $article->title }}</h1>

        <div class="article-meta">
            <a href="{{ route('wiki.show', $article) }}">&larr; Back to article</a>
        </div>

        <ol class="history">
            @foreach ($revisions as $revision)
                <li>
                    <div class="history__summary">{{ $revision->summary ?: '(no summary)' }}</div>
                    <div class="history__meta">
                        {{ $revision->author?->name ?? 'anonymous' }}
                        · <time datetime="{{ $revision->created_at->toIso8601String() }}">{{ $revision->created_at->format('j F Y, H:i') }}</time>
                        · {{ $revision->created_at->diffForHumans() }}
                        @if ($revision->minor)
                            · <span class="badge">minor</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>

        {{ $revisions->links() }}
    </section>
@endsection
