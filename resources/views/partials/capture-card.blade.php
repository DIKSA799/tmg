@php
    $optionSets = [
        'gender' => \App\Enums\Gender::options(),
        'age_band' => \App\Enums\AgeBand::options(),
        'registered_voter_status' => \App\Enums\RegisteredVoterStatus::options(),
        'pvc_status' => \App\Enums\PvcStatus::options(),
        'preferred_language' => \App\Enums\PreferredLanguage::options(),
        'preferred_channel' => \App\Enums\PreferredChannel::options(),
    ];
@endphp

<div class="reveal neo-glass overflow-hidden rounded-[34px]">
    <div class="flex flex-col gap-6 border-b border-[color:var(--line)] p-7 sm:p-9 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-xl">
            <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Live capture</span>
            <h2 class="mt-5 font-display text-3xl font-bold tracking-tight sm:text-4xl">{{ $cardHeading ?? 'Capture Ambassador' }}</h2>
            <p class="mt-3 text-sm leading-relaxed text-[color:var(--ink-soft)] sm:text-base">
                {{ $cardBlurb ?? 'Fields marked' }} <span class="font-semibold text-[color:var(--flag-red)]">*</span>
                {{ $cardBlurbSuffix ?? 'are required. Location is pre-filled to the first available option — use your device location to jump straight to your own ward.' }}
            </p>
        </div>

        {{-- <div class="flex flex-col items-start gap-3">
            <button type="button" class="btn btn-ghost" data-geolocate>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2v3m0 14v3M2 12h3m14 0h3M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                Use my location
            </button>
            <p class="text-xs text-[color:var(--ink-mute)]" data-geo-status role="status" aria-live="polite">No location shared yet.</p>
        </div> --}}
    </div>

    {{-- Success panel --}}
    <div class="hidden p-7 sm:p-9" data-success hidden>
        <div class="mx-auto max-w-lg text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[color:var(--accent-soft)] text-[color:var(--accent-ink)]">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h3 class="mt-5 font-display text-2xl font-bold" data-success-title>Record captured</h3>
            <p class="mt-2 text-sm text-[color:var(--ink-soft)]" data-success-message>Thank you. Your response has been recorded securely.</p>
            <p class="mt-4 inline-flex items-center gap-2 rounded-full border border-[color:var(--line)] px-4 py-2 font-mono text-xs text-[color:var(--ink-soft)]" data-success-reference-row>
                Reference <span data-success-reference class="font-semibold text-[color:var(--ink)]">—</span>
            </p>
            <div class="mt-7">
                <button type="button" class="btn btn-primary" data-capture-again>Capture another Ambassador</button>
            </div>
        </div>
    </div>

    <form class="space-y-10 p-7 sm:p-9" data-capture-form novalidate>
        <input type="hidden" name="idempotency_key" value="" data-idempotency>
        <input type="hidden" name="latitude" value="" data-latitude>
        <input type="hidden" name="longitude" value="" data-longitude>

        <noscript>
            <p class="rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm text-[color:var(--flag-red)]">
                JavaScript is required to search states, wards and polling units. Please enable it to continue.
            </p>
        </noscript>

        {{-- 1 · Identity --}}
        <fieldset class="space-y-6" data-section="identity">
            <legend class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent-ink)]">1</span>
                <span class="font-display text-lg font-bold">Identity</span>
            </legend>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="min-w-0 sm:col-span-2">
                    <label class="field-label" for="full_name">Full name <span class="req">*</span></label>
                    <input id="full_name" name="full_name" type="text" class="input" placeholder="e.g. Amina Seun Emeka" autocomplete="name" required>
                    <p class="field-error" data-error-for="full_name" hidden></p>
                </div>

                <div class="min-w-0 sm:col-span-2">
                    <span class="field-label">Gender <span class="req">*</span></span>
                    <div class="segments">
                        @foreach ($optionSets['gender'] as $value => $label)
                            <label class="segment">
                                <input type="radio" name="gender" value="{{ $value }}" @checked($loop->first)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="field-error" data-error-for="gender" hidden></p>
                </div>

                <div class="min-w-0">
                    <label class="field-label" for="age_band">Age band <span class="req">*</span></label>
                    <select id="age_band" name="age_band" class="input" required>
                        <option value="" disabled selected>Select age band…</option>
                        @foreach ($optionSets['age_band'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="field-error" data-error-for="age_band" hidden></p>
                </div>

                <div class="min-w-0">
                    <label class="field-label" for="phone">Mobile number <span class="req">*</span></label>
                    <input id="phone" name="phone" type="tel" class="input" placeholder="0800 000 0000" inputmode="tel" autocomplete="tel" required>
                    <!-- <p class="field-hint">Stored in +234 format.</p> -->
                    <p class="field-error" data-error-for="phone" hidden></p>
                </div>

                <div class="min-w-0">
                    <label class="field-label" for="whatsapp">WhatsApp number</label>
                    <input id="whatsapp" name="whatsapp" type="tel" class="input" placeholder="0800 000 0000" inputmode="tel" autocomplete="tel">
                    <!-- <p class="field-hint">Optional · stored in +234 format.</p> -->
                    <p class="field-error" data-error-for="whatsapp" hidden></p>
                </div>

                <div class="min-w-0">
                    <label class="field-label" for="email">Email address</label>
                    <input id="email" name="email" type="email" class="input" placeholder="you@example.com" inputmode="email" autocomplete="email">
                    <p class="field-error" data-error-for="email" hidden></p>
                </div>
            </div>
        </fieldset>

        {{-- 2 · Location --}}
        <fieldset class="space-y-6" data-section="location">
            <legend class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent-ink)]">2</span>
                <span class="font-display text-lg font-bold">Location</span>
            </legend>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-combobox name="state_id" label="State" placeholder="Select state…" />
                <x-combobox name="lga_id" label="Local Government Area" placeholder="Select LGA…" :disabled="true" />
                <x-combobox name="ward_id" label="Ward / Registration Area" placeholder="Select ward…" :disabled="true" />
                <x-combobox name="polling_unit_id" label="Polling unit" placeholder="Select polling unit…" hint="Search by name or code." :disabled="true" />
            </div>
        </fieldset>

        {{-- 3 · Volunteer details --}}
        <fieldset class="space-y-6" data-section="volunteer">
            <legend class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent-ink)]">3</span>
                <span class="font-display text-lg font-bold">Ambassador profile</span>
            </legend>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-combobox
                    name="volunteer_category"
                    label="Volunteer category"
                    placeholder="Select a category…"
                    hint="Choose where you can help most."
                    :options="\App\Enums\VolunteerCategory::options()"
                />

                <x-combobox
                    name="occupation"
                    label="Occupation"
                    placeholder="Select your occupation…"
                    :options="\App\Enums\Occupation::options()"
                />

                <div class="min-w-0 sm:col-span-2">
                    <span class="field-label">Do you have a disability? <span class="req">*</span></span>
                    <div class="segments max-w-sm">
                        <label class="segment"><input type="radio" name="has_disability" value="1"><span>Yes</span></label>
                        <label class="segment"><input type="radio" name="has_disability" value="0" checked><span>No</span></label>
                    </div>
                    <p class="field-error" data-error-for="has_disability" hidden></p>
                </div>
            </div>
        </fieldset>

        {{-- 4 · Status & preferences --}}
        <fieldset class="space-y-6" data-section="status">
            <legend class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent-ink)]">4</span>
                <span class="font-display text-lg font-bold">Volunteer Registration status &amp; preferences</span>
            </legend>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="field-label" for="registered_voter_status">Registered volunteer? <span class="req">*</span></label>
                    <select id="registered_voter_status" name="registered_voter_status" class="input" required>
                        <option value="" disabled selected>Select…</option>
                        @foreach ($optionSets['registered_voter_status'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="field-error" data-error-for="registered_voter_status" hidden></p>
                </div>

                <div class="min-w-0" data-pvc-field>
                    <label class="field-label" for="pvc_status">PVC status <span class="req">*</span></label>
                    <select id="pvc_status" name="pvc_status" class="input" required>
                        <option value="" disabled selected>Select…</option>
                        @foreach ($optionSets['pvc_status'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="field-error" data-error-for="pvc_status" hidden></p>
                </div>

                <div class="min-w-0">
                    <label class="field-label" for="preferred_language">Preferred language <span class="req">*</span></label>
                    <select id="preferred_language" name="preferred_language" class="input" required data-other-trigger="preferred_language">
                        <option value="" disabled selected>Select…</option>
                        @foreach ($optionSets['preferred_language'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="field-error" data-error-for="preferred_language" hidden></p>
                </div>

                <div class="min-w-0">
                    <label class="field-label" for="preferred_channel">Preferred channel <span class="req">*</span></label>
                    <select id="preferred_channel" name="preferred_channel" class="input" required data-other-trigger="preferred_channel">
                        <option value="" disabled selected>Select…</option>
                        @foreach ($optionSets['preferred_channel'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="field-error" data-error-for="preferred_channel" hidden></p>
                </div>

                <div class="hidden min-w-0" data-other-field="preferred_language">
                    <label class="field-label" for="preferred_language_other">Specify language <span class="req">*</span></label>
                    <input id="preferred_language_other" name="preferred_language_other" type="text" class="input" placeholder="Type the language" disabled>
                    <p class="field-error" data-error-for="preferred_language_other" hidden></p>
                </div>

                <div class="hidden min-w-0" data-other-field="preferred_channel">
                    <label class="field-label" for="preferred_channel_other">Specify channel <span class="req">*</span></label>
                    <input id="preferred_channel_other" name="preferred_channel_other" type="text" class="input" placeholder="Type the channel" disabled>
                    <p class="field-error" data-error-for="preferred_channel_other" hidden></p>
                </div>
            </div>
        </fieldset>

        {{-- 5 · Consent --}}
        <fieldset class="space-y-6" data-section="consent">
            <legend class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent-ink)]">5</span>
                <span class="font-display text-lg font-bold">Consent</span>
            </legend>

            <div class="grid gap-5">
                <div class="min-w-0">
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4 transition hover:border-[color:var(--accent)]">
                        <input type="checkbox" name="pledge_accepted" value="1" class="checkbox" required>
                        <span class="min-w-0 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                            <span class="block font-semibold text-[color:var(--ink)]">I accept the Ambassador pledge <span class="req">*</span></span>
                            I voluntarily join TMG and undertake to promote its objectives peacefully and lawfully; mobilise and recruit supporters; encourage eligible citizens to participate in elections; respect the freedom of every voter; refrain from violence, intimidation, misinformation, hate speech, bribery or unlawful electoral conduct; protect confidential information entrusted to me; and report suspected electoral irregularities only through lawful channels.
                        </span>
                    </label>
                    <p class="field-error" data-error-for="pledge_accepted" hidden></p>
                </div>

                <!-- <div class="min-w-0">
                    <span class="field-label">Agree to receive relevant communications through the selected channel? <span class="req">*</span></span>
                    <div class="segments max-w-sm">
                        <label class="segment"><input type="radio" name="consent_to_contact" value="1" checked><span>Yes</span></label>
                        <label class="segment"><input type="radio" name="consent_to_contact" value="0"><span>No</span></label>
                    </div>
                    <p class="field-error" data-error-for="consent_to_contact" hidden></p>
                </div> -->

                <!-- <div class="min-w-0">
                    <span class="field-label">Informed of the purpose and consent to storage &amp; processing? <span class="req">*</span></span>
                    <div class="segments max-w-sm">
                        <label class="segment"><input type="radio" name="consent_to_data" value="1" checked><span>Yes</span></label>
                        <label class="segment"><input type="radio" name="consent_to_data" value="0"><span>No</span></label>
                    </div>
                    <p class="field-error" data-error-for="consent_to_data" hidden></p>
                </div> -->
            </div>
        </fieldset>

        <div class="flex flex-col gap-4 border-t border-[color:var(--line)] pt-7 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-md text-xs leading-relaxed text-[color:var(--ink-mute)]">
                Submitting stores the record with the capture time, device context and a duplicate-proof reference.
            </p>
            <button type="submit" class="btn btn-primary w-full sm:w-auto" data-submit>
                <span data-submit-label>Submit record</span>
                <svg data-submit-spinner class="hidden animate-[spin_0.8s_linear_infinite]" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
            </button>
        </div>

        <p class="hidden rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" data-form-error role="alert"></p>
    </form>
</div>
