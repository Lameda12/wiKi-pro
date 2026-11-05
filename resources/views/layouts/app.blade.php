<!DOCTYPE html>
<html lang="en" data-theme="auto" data-size="medium" data-width="narrow">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    {{-- Apply saved reading preferences before first paint to avoid a flash of the wrong theme. --}}
    <script>
        try {
            const p = JSON.parse(localStorage.getItem('wiki:prefs') || '{}');
            const d = document.documentElement.dataset;
            if (['auto', 'light', 'dark', 'sepia', 'contrast'].includes(p.theme)) d.theme = p.theme;
            if (['small', 'medium', 'large', 'xlarge'].includes(p.size)) d.size = p.size;
            if (['narrow', 'wide'].includes(p.width)) d.width = p.width;
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#content" class="skip-link">Skip to content</a>

    <header class="site-header">
        <div class="site-header__inner">
            <a href="{{ route('home') }}" class="brand">
                <span class="brand__mark" aria-hidden="true">W</span>
                <span>{{ config('app.name') }}</span>
            </a>
            <nav class="site-nav" aria-label="Site">
                <a href="{{ route('home') }}">Index</a>
                @auth
                    <span>{{ auth()->user()->name }}</span>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}">Log in</a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main id="content" class="page @yield('page-class')">
        @if (session('status'))
            <div class="flash" role="status">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <p>Text is available under the <a href="https://creativecommons.org/licenses/by-sa/4.0/" rel="license noopener">Creative Commons Attribution-ShareAlike 4.0 License</a>.</p>
    </footer>

    <button type="button" id="rc-trigger" class="reading-controls__trigger" aria-controls="reading-controls" aria-expanded="false" aria-label="Aa: reading preferences" title="Reading preferences">Aa</button>

    <dialog id="reading-controls" class="reading-controls" aria-labelledby="rc-title">
        <h2 id="rc-title">Reading preferences</h2>

        <fieldset>
            <legend>Theme</legend>
            <div class="rc-group">
                @foreach (['auto' => 'Auto', 'light' => 'Light', 'dark' => 'Dark', 'sepia' => 'Sepia', 'contrast' => 'High contrast'] as $value => $label)
                    <button type="button" data-rc="theme" data-value="{{ $value }}" aria-pressed="false">{{ $label }}</button>
                @endforeach
            </div>
        </fieldset>

        <fieldset>
            <legend>Text size</legend>
            <div class="rc-group">
                @foreach (['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large', 'xlarge' => 'Extra large'] as $value => $label)
                    <button type="button" data-rc="size" data-value="{{ $value }}" aria-pressed="false">{{ $label }}</button>
                @endforeach
            </div>
        </fieldset>

        <fieldset>
            <legend>Line width</legend>
            <div class="rc-group">
                @foreach (['narrow' => 'Narrow', 'wide' => 'Wide'] as $value => $label)
                    <button type="button" data-rc="width" data-value="{{ $value }}" aria-pressed="false">{{ $label }}</button>
                @endforeach
            </div>
        </fieldset>

        <form method="dialog" class="reading-controls__close">
            <button type="submit" class="btn">Done</button>
        </form>
    </dialog>
</body>
</html>
