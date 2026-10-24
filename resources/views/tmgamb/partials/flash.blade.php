@if (session('status'))
    <div class="amb-flash amb-flash-ok" role="status">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error') || $errors->any())
    <div class="amb-flash amb-flash-error" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5m0 3.2v.1M10.3 3.9 2.6 18a2 2 0 0 0 1.8 3h15.2a2 2 0 0 0 1.8-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>{{ session('error') ?? $errors->first() }}</span>
    </div>
@endif
