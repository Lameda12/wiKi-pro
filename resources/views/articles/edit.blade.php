@extends('layouts.app')

@section('title', ($isNew ? 'Creating ' : 'Editing ').$article->title)

@section('content')
    <form class="edit-form" method="POST" action="{{ route('wiki.update', $article->slug) }}">
        @csrf
        @method('PUT')

        <h1>{{ $isNew ? 'Creating' : 'Editing' }} {{ $article->title }}</h1>

        <p class="edit-form__hint">
            Write in Markdown. Link to other articles with <code>[[Article]]</code> or <code>[[Article|display text]]</code>;
            links to articles that do not exist yet appear in red.
        </p>

        @if ($errors->any())
            <div class="form-errors" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <label class="edit-form__label" for="article-source">Article source</label>
        <textarea class="edit-form__textarea" id="article-source" name="content" required spellcheck="true">{{ old('content', $source) }}</textarea>

        <div class="edit-form__row">
            <label class="visually-hidden" for="summary">Edit summary</label>
            <input class="edit-form__input" type="text" id="summary" name="summary" maxlength="500" value="{{ old('summary') }}" placeholder="Brief summary of your changes">

            <label class="edit-form__check">
                <input type="checkbox" name="minor" value="1" @checked(old('minor'))>
                This is a minor edit
            </label>

            <button type="submit" class="btn btn--primary">Save</button>

            @unless ($isNew)
                <a href="{{ route('wiki.show', $article) }}" class="btn">Cancel</a>
            @endunless
        </div>
    </form>
@endsection
