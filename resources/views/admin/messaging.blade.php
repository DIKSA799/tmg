<x-layouts.admin title="Messaging" subtitle="Broadcast SMS or email to a location or all records, or to a manual list, with a live delivery log">
    <x-slot:actions>
        <span class="admin-pill">{{ number_format($campaigns->total()) }} campaigns</span>
        <span class="admin-pill">{{ $anyConfigured ? 'Ready to send' : 'Not configured' }}</span>
    </x-slot:actions>

    @if ($errors->any())
        <div class="rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @unless ($anyConfigured)
        <div class="rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
            No channel is configured. Set the Termii/Sendchamp credentials and/or MAIL_MAILER + MAIL_FROM_ADDRESS before sending.
        </div>
    @endunless

    <section class="admin-card" data-msg-compose>
        <h2 class="admin-card-title">Compose a broadcast</h2>
        <p class="admin-card-sub">Pick a channel and audience, write it once, and watch delivery live. Every send is stored with its request and response for troubleshooting.</p>

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));">
            <div>
                <label class="field-label" for="msg-channel">Channel</label>
                <select id="msg-channel" class="admin-input" data-msg-channel>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel->value }}" @selected($channel->value === $defaultChannel)>{{ $channel->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div data-msg-sms-field>
                <label class="field-label" for="msg-provider">SMS provider</label>
                <select id="msg-provider" class="admin-input" data-msg-provider>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider['value'] }}">{{ $provider['label'] }}{{ $provider['configured'] ? '' : ' (not configured)' }}</option>
                    @endforeach
                </select>
                <p class="field-hint">
                    Sending as <strong data-msg-sender>{{ collect($providers)->firstWhere('configured', true)['senderId'] ?? '—' }}</strong>
                    <span data-msg-provider-warning class="text-[color:var(--flag-red)]" hidden>— this provider is not configured.</span>
                </p>
            </div>

            <div data-msg-email-field hidden>
                <span class="field-label">Email mailer</span>
                <p class="admin-input" style="display: flex; align-items: center;">{{ $mailer }}</p>
                <p class="field-hint">Sending from <strong>{{ $fromAddress }}</strong></p>
            </div>
        </div>

        <div class="admin-range mt-4" role="group" aria-label="Audience">
            <button type="button" data-msg-mode="records" class="is-active">By records</button>
            <button type="button" data-msg-mode="list">Manual list</button>
        </div>

        <div class="mt-4 grid gap-4" data-msg-panel="records">
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
                <div>
                    <label class="field-label" for="msg-state">Location</label>
                    <select id="msg-state" class="admin-input" data-msg-state data-lgas-url="{{ route('geography.lgas', ['state' => '__STATE__']) }}">
                        <option value="">All records (nationwide)</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label" for="msg-lga">LGA <span class="text-[color:var(--ink-mute)]">(optional)</span></label>
                    <select id="msg-lga" class="admin-input" data-msg-lga>
                        <option value="">All LGAs in the state</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="field-label" for="msg-test-recipients" data-msg-test-label>Also send to these phone numbers <span class="text-[color:var(--ink-mute)]">(optional test recipients)</span></label>
                <textarea id="msg-test-recipients" class="admin-input" data-msg-test-recipients rows="3" placeholder="08127234628&#10;+2348031234567"></textarea>
                <p class="field-hint">Added on top of the audience so you can confirm delivery. One per line or comma separated.</p>
            </div>

            <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));">
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-3">
                    <input type="checkbox" data-msg-pause-after-test class="checkbox mt-0.5">
                    <span>
                        <span class="block text-sm font-semibold">Send test recipients first, then pause</span>
                        <span class="block text-xs text-[color:var(--ink-mute)]">Only the test recipients go out now. Review the responses, then press Continue to send to everyone.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-3">
                    <input type="checkbox" data-msg-skip-recent class="checkbox mt-0.5">
                    <span>
                        <span class="block text-sm font-semibold">Skip recipients messaged recently</span>
                        <span class="mt-1 flex items-center gap-2 text-xs text-[color:var(--ink-mute)]">
                            Skip anyone messaged in the last
                            <input type="number" min="1" max="365" value="30" data-msg-skip-days class="admin-input !w-20 !px-2 !py-1 text-xs">
                            days
                        </span>
                    </span>
                </label>
            </div>

            <p class="text-sm">Recipients: <span class="num font-semibold" data-msg-count>0</span> record(s) + <span class="num font-semibold" data-msg-test-count>0</span> test recipient(s)</p>
        </div>

        <div class="mt-4 grid gap-4" data-msg-panel="list" hidden>
            <div>
                <label class="field-label" for="msg-manual-recipients" data-msg-manual-label>Phone numbers</label>
                <textarea id="msg-manual-recipients" class="admin-input" data-msg-manual-recipients rows="4" placeholder="08031234567&#10;+2348031234567"></textarea>
                <p class="field-hint">One per line or comma separated. Duplicates and invalid entries are ignored.</p>
            </div>

            <p class="text-sm">Recipients: <span class="num font-semibold" data-msg-manual-count>0</span></p>
        </div>

        <div class="mt-4" data-msg-subject-wrap hidden>
            <label class="field-label" for="msg-subject">Subject</label>
            <input id="msg-subject" class="admin-input" data-msg-subject maxlength="200">
        </div>

        <div class="mt-4">
            <label class="field-label" for="msg-message">Message</label>
            <textarea id="msg-message" class="admin-input" data-msg-message rows="5" maxlength="5000"></textarea>
            <p class="field-hint"><span data-msg-chars>0</span> characters<span data-msg-segments-wrap> &middot; <span data-msg-segments>1</span> SMS</span></p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="button" class="btn btn-primary" data-msg-send @disabled(! $anyConfigured)>Send</button>
            <span class="text-sm text-[color:var(--ink-mute)]" data-msg-status></span>
        </div>

        <div class="mt-4" data-msg-progress hidden>
            <div class="admin-progress"><span data-msg-bar style="width: 0%"></span></div>
            <p class="mt-2 text-sm">
                Sent <span class="num font-semibold" data-msg-sent>0</span> &middot;
                failed <span class="num font-semibold" data-msg-failed>0</span> &middot;
                remaining <span class="num" data-msg-remaining>0</span> of <span class="num" data-msg-total>0</span>
            </p>
            <p class="mt-2 text-sm"><a class="amb-link" href="#" data-msg-detail hidden>View details &amp; headers &rarr;</a></p>
            <p class="mt-2"><button type="button" class="btn btn-primary !px-4 !py-2.5 text-xs" data-msg-continue hidden>Continue sending to everyone</button></p>

            <div class="mt-3 grid gap-2" data-msg-log aria-live="polite" aria-label="Live API responses" style="max-height: 26rem; overflow-y: auto;"></div>
        </div>
    </section>

    <section class="admin-card">
        <h2 class="admin-card-title">Recent campaigns</h2>
        <p class="admin-card-sub">Newest first. Open a campaign to inspect each recipient's request and response.</p>

        <div class="admin-table-wrap" style="max-height: 30rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Channel</th>
                        <th scope="col">Provider</th>
                        <th scope="col">Target</th>
                        <th scope="col">Recipients</th>
                        <th scope="col">Sent</th>
                        <th scope="col">Failed</th>
                        <th scope="col">Status</th>
                        <th scope="col">By</th>
                        <th scope="col">Created</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td><span class="admin-pill">{{ $campaign->channel->label() }}</span></td>
                            <td>{{ $campaign->provider }}</td>
                            <td>{{ $campaign->scope_label }}</td>
                            <td class="num">{{ number_format($campaign->total_recipients) }}</td>
                            <td class="num">{{ number_format($campaign->sent_count) }}</td>
                            <td class="num">{{ number_format($campaign->failed_count) }}</td>
                            <td><span class="admin-pill">{{ ucfirst($campaign->status) }}</span></td>
                            <td>{{ $campaign->admin?->name ?? '—' }}</td>
                            <td class="num">{{ $campaign->created_at?->format('d M Y H:i') }}</td>
                            <td><a class="btn btn-ghost !px-3 !py-2 text-xs" href="{{ route('admin.messaging.show', $campaign) }}">View</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-[color:var(--ink-mute)]">No campaigns yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination mt-4">
            <span>
                Showing {{ number_format($campaigns->firstItem() ?? 0) }}–{{ number_format($campaigns->lastItem() ?? 0) }}
                of {{ number_format($campaigns->total()) }}
            </span>
            <x-admin.pagination :paginator="$campaigns" />
        </div>
    </section>

    @php
        $channelMeta = collect($channels)->map(fn ($channel) => [
            'value' => $channel->value,
            'label' => $channel->label(),
            'subject' => $channel->needsSubject(),
            'recipientLabel' => $channel->recipientLabel(),
            'fieldLabel' => $channel->recipientFieldLabel(),
        ])->values()->all();
    @endphp

    <script>
        window.__MSG = {
            mode: 'compose',
            defaultChannel: {{ Js::from($defaultChannel) }},
            channels: {{ Js::from($channelMeta) }},
            providers: {{ Js::from($providers) }},
            emailConfigured: {{ Js::from($emailConfigured) }},
            countUrl: {{ Js::from(route('admin.messaging.count')) }},
            recentUrl: {{ Js::from(route('admin.messaging.recent')) }},
            storeUrl: {{ Js::from(route('admin.messaging.store')) }},
            processUrl: {{ Js::from(route('admin.messaging.process', ['campaign' => '__ID__'])) }},
            detailUrl: {{ Js::from(route('admin.messaging.show', ['campaign' => '__ID__'])) }},
        };
    </script>
</x-layouts.admin>
