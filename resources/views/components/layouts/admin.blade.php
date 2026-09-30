@props([
    'title' => 'Operations console',
    'subtitle' => null,
    'chrome' => true,
    'actions' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
        <meta name="referrer" content="no-referrer">
        <meta name="theme-color" content="#0b0909">

        <title>{{ $title }} · TMG Console</title>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/admin.js'])
        @endif
    </head>
    <body class="antialiased">
        @if ($chrome)
            <div class="admin-shell">
                <aside class="admin-sidebar">
                    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                        <img src="{{ asset('t2.png') }}" alt="" width="2413" height="2875">
                        <span>
                            <span class="admin-brand-name">Tinubu Must Go</span>
                            <span class="admin-brand-sub">Console</span>
                        </span>
                    </a>

                    <nav class="admin-nav" aria-label="Console">
                        <a href="{{ route('admin.dashboard') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.dashboard')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-4H4v4Zm10-11h6V4h-6v5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            Dashboard
                        </a>
                        <a href="{{ route('admin.records') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.records')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            Records
                        </a>
                        <a href="{{ route('admin.geography') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.geography')])>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.7"/></svg>
                            Geography
                        </a>
                    </nav>

                    <div class="admin-sidebar-foot">
                        <span class="admin-pill">
                            <span class="live-dot" aria-hidden="true"></span>
                            {{ auth('admin')->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="admin-nav-link w-full cursor-pointer bg-transparent text-left">Sign out</button>
                        </form>
                    </div>
                </aside>

                <div class="admin-main">
                    <header class="admin-topbar">
                        <div>
                            <h1 class="admin-title">{{ $title }}</h1>
                            @if ($subtitle)
                                <p class="admin-sub">{{ $subtitle }}</p>
                            @endif
                        </div>
                        @if ($actions)
                            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
                        @endif
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
