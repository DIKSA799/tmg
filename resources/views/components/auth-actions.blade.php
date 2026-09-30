@props(['always' => false])

@auth
    <form method="POST" action="{{ route('logout') }}" class="{{ $always ? '' : 'hidden sm:block' }}">
        @csrf
        <button type="submit" class="btn btn-ghost !px-4 !py-2.5 text-sm">Log out</button>
    </form>
@else
    <a
        href="{{ route('login') }}"
        class="btn btn-ghost !px-4 !py-2.5 text-sm {{ $always ? '' : 'hidden sm:inline-flex' }}"
    >Log in</a>
@endauth
