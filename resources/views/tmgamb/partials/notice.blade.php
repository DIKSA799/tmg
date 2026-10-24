@if (($noticeContent['enabled'] ?? false) && $currentAmbassador->notice_seen_at === null)
    <div class="modal-backdrop" id="notice-modal" data-modal data-auto-open>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="notice-modal-title">
            <div class="modal-head">
                <div>
                    <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Information notice</span>
                    <h2 id="notice-modal-title" class="mt-3">{{ $noticeContent['title'] }}</h2>
                </div>
            </div>

            <p class="text-sm leading-relaxed text-[color:var(--ink-soft)]">
                {!! nl2br(e($noticeContent['body'])) !!}
            </p>

            <form method="POST" action="{{ route('tmgamb.notice.seen') }}" class="mt-6 flex justify-end">
                @csrf
                <button type="submit" class="btn btn-primary">Got it</button>
            </form>
        </div>
    </div>
@endif
