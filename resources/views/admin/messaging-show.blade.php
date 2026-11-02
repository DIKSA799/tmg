<x-layouts.admin :title="'Campaign #'.$campaign->id" :subtitle="$campaign->channel->label().' — '.$campaign->scope_label">
    <x-slot:actions>
        <span class="admin-pill">{{ ucfirst($campaign->status) }}</span>
        <a href="{{ route('admin.messaging') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">Back to Messaging</a>
    </x-slot:actions>

    <section class="admin-card">
        <h2 class="admin-card-title">Summary</h2>

        <div class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));">
            <div><span class="admin-kpi-label">Channel</span><p class="font-semibold">{{ $campaign->channel->label() }}</p></div>
            <div><span class="admin-kpi-label">Provider</span><p class="font-semibold">{{ $campaign->provider }}</p></div>
            <div><span class="admin-kpi-label">Target</span><p class="font-semibold">{{ $campaign->scope_label }}</p></div>
            <div><span class="admin-kpi-label">{{ $campaign->channel->needsSubject() ? 'From' : 'Sender ID' }}</span><p class="font-semibold">{{ $campaign->sender_id ?: '—' }}</p></div>
            <div><span class="admin-kpi-label">Recipients</span><p class="num font-semibold" data-msg-total>{{ number_format($campaign->total_recipients) }}</p></div>
            <div><span class="admin-kpi-label">Sent</span><p class="num font-semibold" data-msg-sent>{{ number_format($campaign->sent_count) }}</p></div>
            <div><span class="admin-kpi-label">Failed</span><p class="num font-semibold" data-msg-failed>{{ number_format($campaign->failed_count) }}</p></div>
            <div><span class="admin-kpi-label">Skipped</span><p class="num font-semibold">{{ number_format($campaign->skipped_count) }}</p></div>
            <div><span class="admin-kpi-label">Created</span><p class="font-semibold">{{ $campaign->created_at?->format('d M Y H:i') }}</p></div>
            <div><span class="admin-kpi-label">By</span><p class="font-semibold">{{ $campaign->admin?->name ?? '—' }}</p></div>
        </div>

        @if ($campaign->subject)
            <p class="mt-4 text-sm"><span class="admin-kpi-label">Subject</span><br><strong>{{ $campaign->subject }}</strong></p>
        @endif

        <p class="mt-4 whitespace-pre-line text-sm">{{ $campaign->message }}</p>

        @if ($hasPending || $campaign->failed_count > 0)
            <div class="mt-4" data-msg-resume data-total="{{ $campaign->total_recipients }}" data-resume="{{ $campaign->paused ? '1' : '0' }}" data-retry-url="{{ route('admin.messaging.retry', $campaign) }}">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($hasPending)
                        <button type="button" class="btn btn-primary" data-msg-resume-run>
                            {{ $campaign->paused ? 'Continue sending to everyone' : 'Resume sending' }}
                        </button>
                    @endif

                    @if ($campaign->failed_count > 0)
                        <button type="button" class="btn btn-ghost" data-msg-retry-run>Retry failed ({{ number_format($campaign->failed_count) }})</button>
                    @endif

                    <span class="text-sm text-[color:var(--ink-mute)]" data-msg-status></span>
                </div>

                @if ($campaign->paused)
                    <p class="mt-3 text-sm font-semibold">Paused after the test recipients — the rest of the list is waiting.</p>
                @endif

                <div class="mt-3 admin-progress"><span data-msg-bar style="width: 0%"></span></div>
                <p class="mt-2 text-sm">Remaining <span class="num" data-msg-remaining>{{ number_format($campaign->total_recipients - $campaign->sent_count - $campaign->failed_count) }}</span></p>
                <p class="mt-2 text-xs text-[color:var(--ink-mute)]">Keep this tab open until it finishes. If it stops, reopen this page and press the button again.</p>

                <div class="mt-3 grid gap-2" data-msg-log aria-live="polite" aria-label="Live API responses" style="max-height: 26rem; overflow-y: auto;"></div>
            </div>
        @endif
    </section>

    <section class="admin-card">
        <h2 class="admin-card-title">Recipients</h2>
        <p class="admin-card-sub">Expand a row to see the exact request sent and the response returned.</p>

        <div class="admin-table-wrap" style="max-height: 40rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Recipient</th>
                        <th scope="col">Record</th>
                        <th scope="col">Status</th>
                        <th scope="col">Provider ID</th>
                        <th scope="col">Error</th>
                        <th scope="col">Exchange</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td class="num">{{ $message->recipient }}</td>
                            <td>{{ $message->voterRecord?->full_name ?? '—' }}</td>
                            <td><span class="admin-pill">{{ ucfirst($message->status) }}</span></td>
                            <td class="num">{{ $message->provider_message_id ?? '—' }}</td>
                            <td style="max-width: 16rem;">{{ $message->error ?? '—' }}</td>
                            <td>
                                <details>
                                    <summary class="cursor-pointer text-xs font-semibold">Request / response</summary>
                                    <div class="mt-2 grid gap-2" style="min-width: 22rem;">
                                        <div>
                                            <span class="admin-kpi-label">Request URL</span>
                                            <pre class="sms-pre">{{ $message->request_url ?? '—' }}</pre>
                                        </div>
                                        <div>
                                            <span class="admin-kpi-label">Request headers</span>
                                            <pre class="sms-pre">{{ json_encode($message->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—' }}</pre>
                                        </div>
                                        <div>
                                            <span class="admin-kpi-label">Request body</span>
                                            <pre class="sms-pre">{{ json_encode($message->request_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—' }}</pre>
                                        </div>
                                        <div>
                                            <span class="admin-kpi-label">Response status</span>
                                            <pre class="sms-pre">{{ $message->response_status ?? '—' }}</pre>
                                        </div>
                                        <div>
                                            <span class="admin-kpi-label">Response headers</span>
                                            <pre class="sms-pre">{{ json_encode($message->response_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—' }}</pre>
                                        </div>
                                        <div>
                                            <span class="admin-kpi-label">Response body</span>
                                            <pre class="sms-pre">{{ $message->response_body ?: '—' }}</pre>
                                        </div>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-[color:var(--ink-mute)]">No recipients in this campaign.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination mt-4">
            <span>
                Showing {{ number_format($messages->firstItem() ?? 0) }}–{{ number_format($messages->lastItem() ?? 0) }}
                of {{ number_format($messages->total()) }}
            </span>
            <x-admin.pagination :paginator="$messages" />
        </div>
    </section>

    <script>
        window.__MSG = {
            mode: 'resume',
            processUrl: {{ Js::from(route('admin.messaging.process', $campaign)) }},
        };
    </script>
</x-layouts.admin>
