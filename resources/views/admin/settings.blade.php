<x-layouts.admin title="Ambassador settings" subtitle="Information notice, terms and community links">
    <x-slot:actions>
        <span class="admin-pill">{{ number_format($links->count()) }} community links</span>
        <span class="admin-pill">{{ $noticeEnabled ? 'Notice on' : 'Notice off' }}</span>
    </x-slot:actions>

    @if ($errors->any())
        <div class="rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="admin-card">
        <h2 class="admin-card-title">Information notice &amp; terms</h2>
        <p class="admin-card-sub">Shown inside the Ambassadors Space. The notice only appears when it is switched on.</p>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="notice_enabled" value="0">
            <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                <input type="checkbox" name="notice_enabled" value="1" class="checkbox" @checked(old('notice_enabled', $noticeEnabled))>
                <span>
                    <span class="block text-sm font-semibold">Show the information notice on sign-in</span>
                    <span class="block text-xs text-[color:var(--ink-mute)]">Ambassadors see it once, then never again unless you reset their acknowledgement.</span>
                </span>
            </label>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="field-label" for="notice_title">Notice title</label>
                    <input id="notice_title" name="notice_title" class="admin-input" value="{{ old('notice_title', $noticeTitle) }}" maxlength="120" required>
                </div>
                <div>
                    <label class="field-label" for="terms_title">Terms title</label>
                    <input id="terms_title" name="terms_title" class="admin-input" value="{{ old('terms_title', $termsTitle) }}" maxlength="120" required>
                </div>
                <div>
                    <label class="field-label" for="notice_body">Notice body</label>
                    <textarea id="notice_body" name="notice_body" class="amb-textarea" style="min-height: 9rem;" maxlength="5000" required>{{ old('notice_body', $noticeBody) }}</textarea>
                </div>
                <div>
                    <label class="field-label" for="terms_body">Terms body</label>
                    <textarea id="terms_body" name="terms_body" class="amb-textarea" style="min-height: 9rem;" maxlength="5000" required>{{ old('terms_body', $termsBody) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Save settings</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2 class="admin-card-title">Community links</h2>
        <p class="admin-card-sub">The floating Telegram and WhatsApp buttons resolve district → state → general.</p>

        <div class="admin-table-wrap" style="max-height: 24rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Platform</th>
                        <th scope="col">Scope</th>
                        <th scope="col">Label</th>
                        <th scope="col">URL</th>
                        <th scope="col">Status</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($links as $link)
                        @php
                            $scopeName = match ($link->scope_type) {
                                'state' => $states->firstWhere('id', $link->scope_id)?->name ?? 'State #'.$link->scope_id,
                                'district' => $districts->firstWhere('id', $link->scope_id)?->name ?? 'District #'.$link->scope_id,
                                default => 'General',
                            };
                        @endphp
                        <tr>
                            <td>{{ $platforms[$link->platform] ?? ucfirst($link->platform) }}</td>
                            <td>{{ $scopeName }} <span class="text-[0.66rem] text-[color:var(--ink-mute)]">({{ $scopeTypes[$link->scope_type] ?? $link->scope_type }})</span></td>
                            <td>{{ $link->label ?: '—' }}</td>
                            <td style="max-width: 20rem; overflow: hidden; text-overflow: ellipsis;">
                                <a class="amb-link" href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">{{ $link->url }}</a>
                            </td>
                            <td>
                                <span class="admin-pill">{{ $link->is_active ? 'Active' : 'Hidden' }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.community-links.destroy', $link) }}" data-confirm="Remove this community link?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost !px-3 !py-2 text-xs">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-[color:var(--ink-mute)]">No community links yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <hr class="amb-divider">

        <h3 class="text-sm font-semibold">Add a link</h3>

        <form method="POST" action="{{ route('admin.community-links.store') }}" class="mt-3 grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
            @csrf

            <div>
                <label class="field-label" for="link-platform">Platform</label>
                <select id="link-platform" name="platform" class="admin-input" required>
                    @foreach ($platforms as $value => $label)
                        <option value="{{ $value }}" @selected(old('platform') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="link-scope">Scope</label>
                <select id="link-scope" name="scope_type" class="admin-input" data-scope-select required>
                    @foreach ($scopeTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('scope_type', 'global') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div data-scope-field="state" hidden>
                <label class="field-label" for="link-state">State</label>
                <select id="link-state" name="state_id" class="admin-input">
                    @foreach ($states as $state)
                        <option value="{{ $state->id }}" @selected(old('state_id') == $state->id)>{{ $state->name }}</option>
                    @endforeach
                </select>
            </div>

            <div data-scope-field="district" hidden>
                <label class="field-label" for="link-district">Senatorial district</label>
                <select id="link-district" name="district_id" class="admin-input">
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected(old('district_id') == $district->id)>{{ $district->state?->name }} — {{ $district->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="link-label">Label</label>
                <input id="link-label" name="label" class="admin-input" value="{{ old('label') }}" maxlength="80" placeholder="TMG Lagos Central">
            </div>

            <div>
                <label class="field-label" for="link-url">URL</label>
                <input id="link-url" name="url" class="admin-input" value="{{ old('url') }}" type="url" placeholder="https://t.me/..." required>
            </div>

            <div class="flex items-end gap-3">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 text-xs">
                    <input type="checkbox" name="is_active" value="1" class="checkbox" @checked(old('is_active', true))>
                    Active
                </label>
                <button type="submit" class="btn btn-primary !px-4 !py-2.5 text-xs">Add link</button>
            </div>
        </form>
    </section>

    <script>
        (function () {
            const scope = document.querySelector('[data-scope-select]');
            const fields = Array.from(document.querySelectorAll('[data-scope-field]'));

            if (!scope) {
                return;
            }

            const sync = () => {
                fields.forEach((field) => {
                    const show = field.dataset.scopeField === scope.value;
                    field.hidden = !show;

                    field.querySelectorAll('select, input').forEach((input) => {
                        input.disabled = !show;
                    });
                });
            };

            scope.addEventListener('change', sync);
            sync();
        })();
    </script>
</x-layouts.admin>
