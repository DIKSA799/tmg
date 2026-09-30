@props([
    'title' => 'Tinubu Must Go · Grassroots Voter Data Capture',
    'description' => 'Tinubu Must Go — grassroots voter data capture for Nigeria. Register voters from state to ward to polling unit in seconds.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#008751">
        <meta name="description" content="{{ $description }}">

        <title>{{ $title }}</title>

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
