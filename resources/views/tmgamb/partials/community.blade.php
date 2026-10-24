@php
    $telegram = $communityLinks['telegram'] ?? null;
    $whatsapp = $communityLinks['whatsapp'] ?? null;
@endphp

@if ($telegram || $whatsapp)
    <div class="amb-fab" aria-label="Join the TMG community">
        @if ($telegram)
            <a
                href="{{ $telegram }}"
                class="amb-fab-btn amb-fab-telegram"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Join the TMG Telegram community"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.7.8l-4.7-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.8 8.8-8c.4-.3-.1-.5-.6-.2L6.6 13.2l-4.6-1.4c-1-.3-1-1 .2-1.5l18-7c.8-.3 1.6.2 1.7.9Z"/></svg>
                <span class="amb-fab-label">Telegram</span>
            </a>
        @endif

        @if ($whatsapp)
            <a
                href="{{ $whatsapp }}"
                class="amb-fab-btn amb-fab-whatsapp"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Join the TMG WhatsApp channel"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a9.9 9.9 0 0 0-8.5 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3.1.8.8-3-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.6-1.3.1-.2 0-.4 0-.5l-.8-1.7c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.2s.9 2.5 1 2.7c.1.2 1.8 2.8 4.4 3.8 1.7.7 2.4.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2 0-.1-.2-.2-.4-.3Z"/></svg>
                <span class="amb-fab-label">WhatsApp</span>
            </a>
        @endif
    </div>
@endif
