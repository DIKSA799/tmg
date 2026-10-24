@php
    $links = [
        ['route' => 'tmgamb.dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
        ['route' => 'tmgamb.leaderboard', 'label' => 'Leaderboard', 'icon' => 'trophy'],
        ['route' => 'tmgamb.profile', 'label' => 'Profile', 'icon' => 'user'],
    ];
@endphp

<header class="amb-nav-wrap">
    <nav class="amb-nav" aria-label="Ambassador navigation">
        <a href="{{ route('tmgamb.dashboard') }}" class="amb-brand">
            <img src="{{ asset('t2.png') }}" alt="" width="2413" height="2875">
            <span>
                <span class="amb-brand-name">TMG Ambassadors</span>
                <span class="amb-brand-sub">{{ $currentAmbassador->senatorialDistrict?->name ?? 'Ambassadors Space' }}</span>
            </span>
        </a>

        <div class="amb-nav-links" role="list">
            @foreach ($links as $link)
                <a
                    href="{{ route($link['route']) }}"
                    @class(['amb-nav-link', 'is-active' => request()->routeIs($link['route'])])
                    @if (request()->routeIs($link['route'])) aria-current="page" @endif
                >
                    @if ($link['icon'] === 'grid')
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4Zm9 0h7v4h-7V4ZM4 13h7v7H4v-7Zm9-3h7v10h-7V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    @elseif ($link['icon'] === 'trophy')
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8v5a4 4 0 0 1-8 0V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 6H5v1a3 3 0 0 0 3 3m8-4h3v1a3 3 0 0 1-3 3M12 13v4m-3 3h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    @else
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.4" stroke="currentColor" stroke-width="1.7"/><path d="M5 20a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    @endif
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <div class="amb-nav-actions">
            <x-theme-toggle />

            <a href="{{ route('tmgamb.profile') }}" class="amb-avatar-btn" aria-label="Open my profile">
                <x-tmgamb.avatar :ambassador="$currentAmbassador" size="sm" />
            </a>

            <form method="POST" action="{{ route('tmgamb.logout') }}">
                @csrf
                <button type="submit" class="icon-btn" aria-label="Sign out" title="Sign out">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5h4v14h-4M10 8l-4 4 4 4m-4-4h9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </form>
        </div>
    </nav>
</header>
