@if ($currentAmbassador->terms_accepted_at === null)
    <div class="modal-backdrop" id="terms-modal" data-modal data-auto-open>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="terms-modal-title">
            <div class="modal-head">
                <div>
                    <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Required</span>
                    <h2 id="terms-modal-title" class="mt-3">{{ $termsContent['title'] }}</h2>
                </div>
                <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
            </div>

            <p class="text-sm leading-relaxed text-[color:var(--ink-soft)]">
                {!! nl2br(e($termsContent['body'])) !!}
            </p>

            <form method="POST" action="{{ route('tmgamb.terms.accept') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                @csrf
                <p class="text-xs text-[color:var(--ink-mute)]">You can read this again from your profile page.</p>
                <button type="submit" class="btn btn-primary">I accept the terms</button>
            </form>
        </div>
    </div>
@endif
