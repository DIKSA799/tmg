<div class="modal-backdrop" id="confirm-modal" data-modal>
    <div
        class="modal"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title"
        aria-describedby="confirm-modal-message"
        style="max-width: 30rem;"
    >
        <div class="modal-head">
            <div>
                <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Confirm</span>
                <h2 id="confirm-modal-title" class="mt-3" data-confirm-title>Please confirm</h2>
            </div>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
        </div>

        <p id="confirm-modal-message" class="text-sm leading-relaxed text-[color:var(--ink-soft)]" data-confirm-message></p>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-primary" data-confirm-submit>Confirm</button>
        </div>
    </div>
</div>
