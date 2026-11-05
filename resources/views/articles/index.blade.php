@extends('layouts.app')

@section('title', 'All articles')

@section('content')
    <article class="prose">
        <h1>All articles</h1>

        <p>Every article in this wiki, alphabetically. Pick one to read, or follow a red link from any article to write the page it is missing.</p>

        <ul class="article-list">
            @forelse ($articles as $article)
                <li><a href="{{ route('wiki.show', $article) }}">{{ $article->title }}</a></li>
            @empty
                <li>No articles yet.</li>
            @endforelse
        </ul>

        {{ $articles->links() }}
    </article>
@endsection
