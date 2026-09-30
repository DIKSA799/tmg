<x-layouts.admin title="Records" subtitle="Every voter record captured by the register">
    <x-slot:actions>
        <span class="admin-pill">{{ number_format($records->total()) }} matching</span>
        <span class="admin-pill">Page {{ $records->currentPage() }} of {{ max(1, $records->lastPage()) }}</span>
    </x-slot:actions>

    <form class="admin-card admin-grid" method="GET" action="{{ route('admin.records') }}" style="grid-template-columns: repeat(auto-fit, minmax(10.5rem, 1fr));">
        <div>
            <label class="field-label" for="f-search">Search</label>
            <input id="f-search" class="admin-input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or phone">
        </div>

        <div>
            <label class="field-label" for="f-state">State</label>
            <select id="f-state" class="admin-input" name="state_id">
                <option value="">All states</option>
                @foreach ($states as $state)
                    <option value="{{ $state->id }}" @selected(($filters['state_id'] ?? null) == $state->id)>{{ $state->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="field-label" for="f-lga">LGA</label>
            <select id="f-lga" class="admin-input" name="lga_id" @disabled($lgas->isEmpty())>
                <option value="">All LGAs</option>
                @foreach ($lgas as $lga)
                    <option value="{{ $lga->id }}" @selected(($filters['lga_id'] ?? null) == $lga->id)>{{ $lga->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="field-label" for="f-gender">Gender</label>
            <select id="f-gender" class="admin-input" name="gender">
                <option value="">Any</option>
                @foreach ($options['gender'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['gender'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="field-label" for="f-age">Age band</label>
            <select id="f-age" class="admin-input" name="age_band">
                <option value="">Any</option>
                @foreach ($options['age_band'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['age_band'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="field-label" for="f-pvc">PVC status</label>
            <select id="f-pvc" class="admin-input" name="pvc_status">
                <option value="">Any</option>
                @foreach ($options['pvc_status'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['pvc_status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="field-label" for="f-voter">Registered voter</label>
            <select id="f-voter" class="admin-input" name="voter_status">
                <option value="">Any</option>
                @foreach ($options['voter_status'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['voter_status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="field-label" for="f-consent">Consent</label>
            <select id="f-consent" class="admin-input" name="consent">
                <option value="">Any</option>
                <option value="contact" @selected(($filters['consent'] ?? null) === 'contact')>Contact given</option>
                <option value="data" @selected(($filters['consent'] ?? null) === 'data')>Data processing given</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="btn btn-primary !px-4 !py-2.5 text-xs">Apply</button>
            <a href="{{ route('admin.records') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">Reset</a>
        </div>
    </form>

    <section class="admin-card">
        <div class="admin-table-wrap" style="max-height: 34rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Gender</th>
                        <th scope="col">Age</th>
                        <th scope="col">State</th>
                        <th scope="col">LGA</th>
                        <th scope="col">Ward</th>
                        <th scope="col">Polling unit</th>
                        <th scope="col">PVC</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Consent</th>
                        <th scope="col">Captured</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>{{ $record->full_name }}</td>
                            <td class="num">{{ $record->phone }}</td>
                            <td>{{ \App\Enums\Gender::tryFrom($record->gender)?->label() ?? $record->gender }}</td>
                            <td class="num">{{ \App\Enums\AgeBand::tryFrom($record->age_band)?->label() ?? $record->age_band }}</td>
                            <td>{{ $record->state?->name }}</td>
                            <td>{{ $record->lga?->name }}</td>
                            <td>{{ $record->ward?->name }}</td>
                            <td>{{ $record->pollingUnit?->name }}</td>
                            <td>{{ \App\Enums\PvcStatus::tryFrom($record->pvc_status)?->label() ?? $record->pvc_status }}</td>
                            <td>{{ \App\Enums\RegisteredVoterStatus::tryFrom($record->registered_voter_status)?->label() ?? $record->registered_voter_status }}</td>
                            <td>
                                <span class="admin-pill">{{ $record->consent_to_contact ? 'Contact' : 'No contact' }}</span>
                            </td>
                            <td class="num">{{ $record->captured_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-10 text-center text-[color:var(--ink-mute)]">No records match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination mt-4">
            <span>
                Showing {{ number_format($records->firstItem() ?? 0) }}–{{ number_format($records->lastItem() ?? 0) }}
                of {{ number_format($records->total()) }}
            </span>
            <x-admin.pagination :paginator="$records" />
        </div>
    </section>
</x-layouts.admin>
