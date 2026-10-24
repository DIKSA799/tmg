@php
    $items = [
        ['route' => 'tmgamb.dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
        ['route' => 'tmgamb.leaderboard', 'label' => 'Leaders', 'icon' => 'trophy'],
        ['route' => 'tmgamb.profile', 'label' => 'Profile', 'icon' => 'user'],
    ];
@endphp

<nav class="amb-bottom-nav" aria-label="Ambassador navigation">
    @foreach ($items as $item)
        <a
            href="{{ route($item['route']) }}"
            @class(['amb-bottom-link', 'is-active' => request()->routeIs($item['route'])])
            @if (request()->routeIs($item['route'])) aria-current="page" @endif
        >
            @if ($item['icon'] === 'grid')
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4Zm9 0h7v4h-7V4ZM4 13h7v7H4v-7Zm9-3h7v10h-7V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
            @elseif ($item['icon'] === 'trophy')
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8v5a4 4 0 0 1-8 0V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 6H5v1a3 3 0 0 0 3 3m8-4h3v1a3 3 0 0 1-3 3M12 13v4m-3 3h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            @else
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.4" stroke="currentColor" stroke-width="1.7"/><path d="M5 20a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            @endif
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
