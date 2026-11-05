@extends('layouts.app')

@section('title', 'Not found')

@section('content')
    <section class="prose">
        <h1>Page not found</h1>

        @php($missing = request()->route('slug'))

        @if (request()->routeIs('wiki.show') && is_string($missing) && $missing !== '')
            <p>There is no article called <strong>{{ $missing }}</strong> yet.</p>
            <p>
                <a class="btn btn--primary" href="{{ route('wiki.edit', $missing) }}">Create this article</a>
                <a class="btn" href="{{ route('home') }}">Browse the index</a>
            </p>
        @else
            <p>That page does not exist.</p>
            <p><a class="btn" href="{{ route('home') }}">Browse the index</a></p>
        @endif
    </section>
@endsection
