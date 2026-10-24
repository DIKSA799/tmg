@php
    $nomination = $ambassador->nomination;
    $shareUrl = $nomination ? route('tmgamb.nominee', $nomination->public_id) : null;
    $reference = $ambassador->voterRecord?->reference;
@endphp

<x-layouts.tmgamb title="My profile · TMG Ambassadors" description="Your read-only ambassador profile and photo.">
    <div class="space-y-4">
        <section class="amb-hero">
            <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                <x-tmgamb.avatar :ambassador="$ambassador" size="xl" />

                <div class="min-w-0 flex-1">
                    <span class="amb-hero-eyebrow">
                        <span class="chip-dot" aria-hidden="true"></span>
                        Ambassador profile
                    </span>
                    <h1 class="mt-2 !text-[clamp(1.7rem,3.4vw,2.6rem)]">{{ $ambassador->name }}</h1>
                    <p class="mt-2 text-sm text-[color:var(--ink-soft)]">
                        {{ $ambassador->senatorialDistrict?->name }} · {{ $ambassador->state?->name }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span class="amb-badge">Joined {{ $ambassador->created_at?->format('M Y') }}</span>
                        @if ($nomination)
                            <span class="amb-badge amb-badge-live">{{ number_format($nomination->votes_count) }} vote(s)</span>
                        @endif
                        @if ($ambassador->last_login_at)
                            <span class="amb-badge">Last sign-in {{ $ambassador->last_login_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>

                @if ($nomination)
                    <a href="{{ $shareUrl }}" class="btn btn-primary w-full sm:w-auto">View my nomination</a>
                @else
                    <a href="{{ route('tmgamb.dashboard') }}" class="btn btn-primary w-full sm:w-auto">Nominate myself</a>
                @endif
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-[1.3fr_1fr]">
            <section class="amb-card">
                <h2 class="amb-card-title">Your details</h2>
                <p class="amb-card-sub">These come from your registration record and cannot be edited here.</p>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <span class="amb-label">Full name</span>
                        <span class="amb-value">{{ $ambassador->name }}</span>
                    </div>
                    <div>
                        <span class="amb-label">Phone number</span>
                        <span class="amb-value">{{ $ambassador->phone }}</span>
                    </div>
                    <div>
                        <span class="amb-label">Email</span>
                        <span class="amb-value">{{ $ambassador->email ?? 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="amb-label">Senatorial district</span>
                        <span class="amb-value">{{ $ambassador->senatorialDistrict?->name ?? 'Not assigned' }}</span>
                    </div>
                    <div>
                        <span class="amb-label">State</span>
                        <span class="amb-value">{{ $ambassador->state?->name ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="amb-label">Local government area</span>
                        <span class="amb-value">{{ $ambassador->lga?->name ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="amb-label">Ward</span>
                        <span class="amb-value">{{ $ambassador->ward?->name ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="amb-label">Polling unit</span>
                        <span class="amb-value">{{ $ambassador->pollingUnit?->name ?? '—' }}</span>
                    </div>
                </div>

                <hr class="amb-divider">

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <span class="amb-label">TMG number · password reset code</span>
                        <span
                            id="tmg-reference"
                            class="amb-secret mt-1"
                            data-secret="{{ $reference ?? '—' }}"
                            data-mask="••••••••••••••••"
                        >••••••••••••••••</span>
                    </div>
                    <button type="button" class="btn btn-ghost !px-3 !py-2 text-xs" data-reveal="tmg-reference">Reveal</button>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-[color:var(--ink-mute)]">
                    Your TMG number is your initial password and your permanent reset code. Keep it
                    private — anyone with your phone number or email and this number can sign in as you.
                </p>
            </section>

            <div class="space-y-4">
                <section class="amb-card">
                    <h2 class="amb-card-title">Profile photo</h2>
                    <p class="amb-card-sub">Shown to ambassadors in your zone and on the leaderboard.</p>

                    <form method="POST" action="{{ route('tmgamb.profile.photo') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div class="flex items-center gap-4">
                            <div class="amb-photo-frame relative">
                                <span class="grid h-full w-full place-items-center font-display text-2xl font-bold text-[color:var(--accent-ink)]">
                                    {{ $ambassador->initials() }}
                                </span>
                                <img
                                    id="photoPreview"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    @if (! $ambassador->photo_path) hidden @endif
                                    src="{{ $ambassador->photoUrl() ?? '' }}"
                                    alt=""
                                >
                            </div>

                            <div class="min-w-0">
                                <label class="btn btn-ghost cursor-pointer !px-3 !py-2 text-xs">
                                    Choose photo
                                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-input="#photoPreview" required>
                                </label>
                                <p class="field-hint">Square crop · WebP · up to 5 MB.</p>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Upload photo</button>
                    </form>
                </section>

                <section class="amb-card">
                    <h2 class="amb-card-title">Nomination</h2>

                    @if ($nomination)
                        <p class="amb-card-sub">You are on the {{ $ambassador->senatorialDistrict?->name }} ballot.</p>

                        <div class="flex items-center justify-between gap-2">
                            <span class="amb-badge amb-badge-live">{{ number_format($nomination->votes_count) }} vote(s)</span>
                            <a href="{{ $shareUrl }}" class="text-xs font-semibold text-[color:var(--accent)]">Open →</a>
                        </div>

                        <button type="button" class="btn btn-ghost mt-3 w-full !px-3 !py-2 text-xs" data-copy="{{ $shareUrl }}">
                            <span data-copy-label>Copy my share link</span>
                        </button>
                    @else
                        <p class="amb-card-sub">You have not nominated yourself yet.</p>
                        <a href="{{ route('tmgamb.dashboard') }}" class="btn btn-primary w-full">Nominate myself</a>
                    @endif
                </section>

                <section class="amb-card">
                    <h2 class="amb-card-title">Change password</h2>
                    <p class="amb-card-sub">Case-insensitive · at least 8 characters.</p>

                    <form method="POST" action="{{ route('tmgamb.password.update') }}" class="space-y-3">
                        @csrf

                        <div>
                            <label class="field-label" for="current_password">Current password</label>
                            <input
                                id="current_password"
                                name="current_password"
                                type="password"
                                class="input uppercase"
                                autocomplete="current-password"
                                autocapitalize="characters"
                                spellcheck="false"
                                data-uppercase
                                required
                            >
                            @error('current_password')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="field-label" for="new_password">New password</label>
                            <input
                                id="new_password"
                                name="password"
                                type="password"
                                class="input uppercase"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="64"
                                autocapitalize="characters"
                                spellcheck="false"
                                data-uppercase
                                required
                            >
                            @error('password')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="field-label" for="password_confirmation">Confirm new password</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                class="input uppercase"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="64"
                                autocapitalize="characters"
                                spellcheck="false"
                                data-uppercase
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Update password</button>
                    </form>
                </section>

                <section class="amb-card">
                    <h2 class="amb-card-title">Acknowledgements</h2>
                    <div class="space-y-2 text-xs text-[color:var(--ink-soft)]">
                        <p>
                            Terms of service:
                            <strong>{{ $ambassador->terms_accepted_at ? 'accepted '.$ambassador->terms_accepted_at->format('d M Y') : 'not accepted yet' }}</strong>
                        </p>
                        <p>
                            Information notice:
                            <strong>{{ $ambassador->notice_seen_at ? 'seen '.$ambassador->notice_seen_at->format('d M Y') : 'not seen yet' }}</strong>
                        </p>
                    </div>

                    <form method="POST" action="{{ route('tmgamb.logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-ghost w-full !px-3 !py-2 text-xs">Sign out</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-layouts.tmgamb>
