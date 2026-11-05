@extends('layouts.app')

@section('title', $article->title)
@section('page-class', count($toc) > 1 ? 'has-toc' : '')

@section('content')
    <article class="prose">
        <h1>{{ $article->title }}</h1>

        <div class="article-meta">
            Edited by {{ $revision->author?->name ?? 'anonymous' }}
            · <time datetime="{{ $revision->created_at->toIso8601String() }}">{{ $revision->created_at->diffForHumans() }}</time>
            · <a href="{{ route('wiki.edit', $article) }}">Edit</a>
            · <a href="{{ route('wiki.history', $article) }}">History</a>
        </div>

        {!! $html !!}
    </article>

    @if (count($toc) > 1)
        <aside class="toc" aria-label="Contents">
            <h2>Contents</h2>
            <ol>
                @foreach ($toc as $item)
                    <li class="toc__level-{{ $item['level'] }}"><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                @endforeach
            </ol>
        </aside>
    @endif
@endsection
