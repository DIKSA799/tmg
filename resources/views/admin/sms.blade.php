<x-layouts.admin title="SMS" subtitle="Broadcast to a state or LGA, or to a manual list of numbers, via Termii or Sendchamp">
    <x-slot:actions>
        <span class="admin-pill">{{ number_format($campaigns->total()) }} campaigns</span>
        <span class="admin-pill">{{ collect($providers)->where('configured', true)->count() }} of {{ count($providers) }} providers ready</span>
    </x-slot:actions>

    @if ($errors->any())
        <div class="rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @unless ($anyConfigured)
        <div class="rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
            No SMS provider is configured. Set the API key and sender id for Termii and/or Sendchamp in the environment before sending.
        </div>
    @endunless

    <section class="admin-card" data-sms-compose>
        <h2 class="admin-card-title">Compose a broadcast</h2>
        <p class="admin-card-sub">Pick a provider, choose who receives the message, type it once, and watch delivery live. Every send is stored with its request and response for troubleshooting.</p>

        <div class="mt-4" style="max-width: 24rem;">
            <label class="field-label" for="sms-provider">Provider</label>
            <select id="sms-provider" class="admin-input" data-sms-provider>
                @foreach ($providers as $provider)
                    <option value="{{ $provider['value'] }}" @selected($provider['value'] === $defaultProvider)>
                        {{ $provider['label'] }}{{ $provider['configured'] ? '' : ' (not configured)' }}
                    </option>
                @endforeach
            </select>
            <p class="field-hint">
                Sending as <strong data-sms-sender>{{ collect($providers)->firstWhere('value', $defaultProvider)['senderId'] ?? '—' }}</strong>
                <span data-sms-provider-warning class="text-[color:var(--flag-red)]" hidden>— this provider is not configured.</span>
            </p>
        </div>

        <div class="admin-range mt-4" role="group" aria-label="Recipients">
            <button type="button" data-sms-mode="location" class="is-active">By location</button>
            <button type="button" data-sms-mode="manual">Manual numbers</button>
        </div>

        <div class="mt-4 grid gap-4" data-sms-panel="location">
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
                <div>
                    <label class="field-label" for="sms-state">State</label>
                    <select id="sms-state" class="admin-input" data-sms-state data-lgas-url="{{ route('geography.lgas', ['state' => '__STATE__']) }}">
                        <option value="">Select a state</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label" for="sms-lga">LGA <span class="text-[color:var(--ink-mute)]">(optional)</span></label>
                    <select id="sms-lga" class="admin-input" data-sms-lga>
                        <option value="">All LGAs in the state</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="field-label" for="sms-test-numbers">Also send to these numbers <span class="text-[color:var(--ink-mute)]">(optional test recipients)</span></label>
                <textarea id="sms-test-numbers" class="admin-input" data-sms-test-numbers rows="3" placeholder="08127234628&#10;+2348031234567"></textarea>
                <p class="field-hint">Added on top of the location so you can confirm delivery. One per line or comma separated.</p>
            </div>

            <p class="text-sm">Recipients: <span class="num font-semibold" data-sms-count>0</span> record(s) + <span class="num font-semibold" data-sms-test-count>0</span> test number(s)</p>
        </div>

        <div class="mt-4 grid gap-4" data-sms-panel="manual" hidden>
            <div>
                <label class="field-label" for="sms-numbers">Phone numbers</label>
                <textarea id="sms-numbers" class="admin-input" data-sms-numbers rows="4" placeholder="08031234567&#10;+2348031234567"></textarea>
                <p class="field-hint">One per line or comma separated. Duplicates and invalid numbers are ignored.</p>
            </div>

            <p class="text-sm">Recipients: <span class="num font-semibold" data-sms-number-count>0</span> number(s)</p>
        </div>

        <div class="mt-4">
            <label class="field-label" for="sms-message">Message</label>
            <textarea id="sms-message" class="admin-input" data-sms-message rows="5" maxlength="1000"></textarea>
            <p class="field-hint">
                <span data-sms-chars>0</span> characters &middot; <span data-sms-segments>1</span> SMS
            </p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="button" class="btn btn-primary" data-sms-send @disabled(! $anyConfigured)>Send SMS</button>
            <span class="text-sm text-[color:var(--ink-mute)]" data-sms-status></span>
        </div>

        <div class="mt-4" data-sms-progress hidden>
            <div class="admin-progress"><span data-sms-bar style="width: 0%"></span></div>
            <p class="mt-2 text-sm">
                Sent <span class="num font-semibold" data-sms-sent>0</span> &middot;
                failed <span class="num font-semibold" data-sms-failed>0</span> &middot;
                remaining <span class="num" data-sms-remaining>0</span> of <span class="num" data-sms-total>0</span>
            </p>
            <p class="mt-2 text-sm"><a class="amb-link" href="#" data-sms-detail hidden>View details &amp; headers &rarr;</a></p>

            <div class="mt-3 grid gap-2" data-sms-log aria-live="polite" aria-label="Live API responses" style="max-height: 26rem; overflow-y: auto;"></div>
        </div>
    </section>

    <section class="admin-card">
        <h2 class="admin-card-title">Recent campaigns</h2>
        <p class="admin-card-sub">Newest first. Open a campaign to inspect each number's request and response.</p>

        <div class="admin-table-wrap" style="max-height: 30rem;">
            <table class="admin-table">
                <thead>
                    <tr>
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
                            <td>{{ $campaign->provider->label() }}</td>
                            <td>{{ $campaign->scope_label }}</td>
                            <td class="num">{{ number_format($campaign->total_recipients) }}</td>
                            <td class="num">{{ number_format($campaign->sent_count) }}</td>
                            <td class="num">{{ number_format($campaign->failed_count) }}</td>
                            <td><span class="admin-pill">{{ ucfirst($campaign->status) }}</span></td>
                            <td>{{ $campaign->admin?->name ?? '—' }}</td>
                            <td class="num">{{ $campaign->created_at?->format('d M Y H:i') }}</td>
                            <td><a class="btn btn-ghost !px-3 !py-2 text-xs" href="{{ route('admin.sms.show', $campaign) }}">View</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-[color:var(--ink-mute)]">No campaigns yet.</td>
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

    <script>
        window.__SMS = {
            mode: 'compose',
            countUrl: {{ Js::from(route('admin.sms.count')) }},
            storeUrl: {{ Js::from(route('admin.sms.store')) }},
            processUrl: {{ Js::from(route('admin.sms.process', ['campaign' => '__ID__'])) }},
            detailUrl: {{ Js::from(route('admin.sms.show', ['campaign' => '__ID__'])) }},
            providers: {{ Js::from($providers) }},
        };
    </script>
</x-layouts.admin>
