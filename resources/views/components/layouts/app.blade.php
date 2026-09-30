@props([
    'title' => 'Tinubu Must Go! | Civic Movement',
    'description' => 'Public information, movement identity, privacy information and downloadable media assets.',
    'image' => 't1.png',
    'imageWidth' => 2411,
    'imageHeight' => 3415,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f1eeee">
        <meta name="robots" content="index,follow,max-image-preview:large">

        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Icons --}}
        <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="512x512">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        {{-- Social preview --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Tinubu Must Go">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset($image) }}">
        <meta property="og:image:width" content="{{ $imageWidth }}">
        <meta property="og:image:height" content="{{ $imageHeight }}">
        <meta property="og:image:alt" content="Tinubu Must Go movement artwork">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title }}">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ asset($image) }}">

        {{-- Resolve the theme before first paint so there is no flash. --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('tmg.theme');
                    var theme = stored === 'light' || stored === 'dark'
                        ? stored
                        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

                    document.documentElement.setAttribute('data-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            })();
        </script>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="antialiased">
        <div class="top-progress" data-top-progress></div>

        {{ $slot }}
    </body>
</html>
