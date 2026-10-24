@props([
    'title' => 'TMG Ambassadors Space',
    'description' => 'The TMG Ambassador nomination and voting space.',
    'bare' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f1eeee">
        <meta name="robots" content="noindex,nofollow,noarchive">
        <meta name="referrer" content="strict-origin-when-cross-origin">

        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">

        <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="512x512">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('tmg.theme');
                    var theme = stored === 'dark' ? 'dark' : 'light';

                    document.documentElement.setAttribute('data-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            })();
        </script>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/tmgamb.js'])
        @endif
    </head>
    <body class="amb-surface antialiased">
        <div class="amb-grid" aria-hidden="true"></div>
        <div class="amb-orb amb-orb-a" aria-hidden="true"></div>
        <div class="amb-orb amb-orb-b" aria-hidden="true"></div>
        <div class="amb-orb amb-orb-c" aria-hidden="true"></div>

        @if ($currentAmbassador ?? null)
            @include('tmgamb.partials.nav')
        @endif

        @if ($bare)
            {{ $slot }}
        @else
            <main class="amb-main" id="main">
                @include('tmgamb.partials.flash')

                {{ $slot }}
            </main>
        @endif

        @if ($currentAmbassador ?? null)
            @include('tmgamb.partials.community')
            @include('tmgamb.partials.bottom-nav')
            @include('tmgamb.partials.terms')
            @include('tmgamb.partials.notice')
        @endif
    </body>
</html>
