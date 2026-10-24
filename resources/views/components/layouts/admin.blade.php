@props([
    'title' => 'Operations console',
    'subtitle' => null,
    'chrome' => true,
    'actions' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
        <meta name="referrer" content="no-referrer">
        <meta name="theme-color" content="#0b0909">

        <title>{{ $title }} · TMG Console</title>

        {{-- Resolve the console theme before first paint so there is no flash.
             The console opens dark by default, but a saved light choice wins. --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('tmg.theme');
                    var theme = stored === 'light' ? 'light' : 'dark';

                    document.documentElement.setAttribute('data-theme', theme);

                    var meta = document.querySelector('meta[name="theme-color"]');
                    if (meta) {
                        meta.setAttribute('content', theme === 'dark' ? '#0b0909' : '#f1eeee');
                    }
                } catch (error) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            })();
        </script>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/admin.js'])
        @endif
    </head>
    <body class="antialiased admin-surface">
        @if ($chrome)
            <div class="admin-nav-backdrop" data-sidebar-backdrop data-sidebar-close aria-hidden="true"></div>

            <div class="admin-shell">
                <aside class="admin-sidebar" id="admin-sidebar" data-admin-sidebar aria-label="Console navigation">
                    <div class="admin-sidebar-head">
                        <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                            <img src="{{ asset('t2.png') }}" alt="" width="2413" height="2875">
                            <span>
                                <span class="admin-brand-name">Tinubu Must Go</span>
                                <span class="admin-brand-sub">Console</span>
                            </span>
                        </a>

                        <button type="button" class="admin-sidebar-close" data-sidebar-close aria-label="Close navigation">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </button>
                    </div>

                    <nav class="admin-nav" aria-label="Console">
                        <span class="admin-nav-label">Workspace</span>

                        <a href="{{ route('admin.dashboard') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.dashboard')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-4H4v4Zm10-11h6V4h-6v5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            Dashboard
                        </a>
                        <a href="{{ route('admin.leaderboard') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.leaderboard*')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8v5a4 4 0 0 1-8 0V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 6H5v1a3 3 0 0 0 3 3m8-4h3v1a3 3 0 0 1-3 3M12 13v4m-3 3h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            Leaderboard
                        </a>
                        <a href="{{ route('admin.records') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.records')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            Records
                        </a>
                        <a href="{{ route('admin.geography') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.geography')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.7"/></svg>
                            Geography
                        </a>
                        <a href="{{ route('admin.senatorial') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.senatorial')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M4 9h16M9 9v10" stroke="currentColor" stroke-width="1.7"/></svg>
                            Senatorial zones
                        </a>
                        <a href="{{ route('admin.settings') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.settings')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                            Settings
                        </a>
                    </nav>

                    <div class="admin-sidebar-foot">
                        <span class="admin-pill">
                            <span class="live-dot" aria-hidden="true"></span>
                            {{ auth('admin')->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="admin-nav-link w-full cursor-pointer bg-transparent text-left">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5h4v14h-4M10 8l-4 4 4 4m-4-4h9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </aside>

                <div class="admin-main">
                    <header class="admin-topbar">
                        <div class="admin-topbar-lead">
                            <button type="button" class="admin-menu-btn" data-sidebar-toggle aria-expanded="false" aria-controls="admin-sidebar" aria-label="Open navigation">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </button>

                            <div class="min-w-0">
                                <h1 class="admin-title">{{ $title }}</h1>
                                @if ($subtitle)
                                    <p class="admin-sub">{{ $subtitle }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="admin-topbar-actions">
                            @if ($actions)
                                {{ $actions }}
                            @endif

                            <x-theme-toggle />
                        </div>
                    </header>

                    <main class="admin-content">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        @else
            {{ $slot }}
        @endif
    </body>
</html>
