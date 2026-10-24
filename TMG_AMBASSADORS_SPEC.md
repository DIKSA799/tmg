# TMG Ambassadors Platform — Engineering Spec

> Status: implemented
> Scope: ambassador accounts, senatorial zones, zone dashboard, nominations, voting,
> leaderboards, profile, community links, admin geography + settings.

---

## 1. Product summary

Every person captured by the registration desk becomes a **TMG Ambassador** with a
login account. Ambassadors sign in at **`/tmgamb`** with:

- **Identity** — their phone number **or** email address
- **Password** — their generated **TMG number** (the voter-record `reference`,
  e.g. `TMG-DEL-NDW-UTAGBA-033-K7Q2M9`)

After sign-in they land on a dashboard scoped to **their senatorial zone**. They can:

1. See and search ambassadors in their own senatorial zone (name, phone, polling unit).
2. Nominate themselves (accept terms, upload a photo) and appear on the leaderboard with 1 vote.
3. Invite another ambassador to be nominated; the invitee accepts or rejects.
4. Vote for one nominee in their zone (accept terms first), with duplicate-proof atomic counting.
5. See the zone leaderboard (1/2/3 badges) and the state top 5.
6. View their own profile (read-only) and upload a profile photo.
7. Join the zone/state/general Telegram and WhatsApp communities.

Admins can browse senatorial zones and their LGAs per state, and configure the
information notice, terms text, and community links.

---

## 2. Accounts & authentication

### 2.1 Model

A dedicated `ambassadors` table + `ambassador` guard. The existing `users`/`web`
guard remains the registration-desk operator login and is untouched.

Each ambassador row stores:

| Field | Notes |
|---|---|
| `voter_record_id` | unique FK to `voter_records` |
| `name` | copied from `full_name` |
| `phone` | unique, E.164 (`+234...`) |
| `email` | nullable, unique when present |
| `password` | bcrypt hash of the **uppercased, trimmed TMG number** |
| `state_id`, `lga_id`, `ward_id`, `polling_unit_id` | copied from the record |
| `senatorial_district_id` | resolved from the LGA |
| `photo_path` | public disk path |
| `terms_accepted_at`, `notice_seen_at`, `last_login_at` | timestamps |

### 2.2 Login rules

- Identity containing `@` → email lookup; otherwise normalize to `+234...` and look up phone.
- Password comparison is case-insensitive by normalizing to uppercase before hashing/attempting.
- Throttle: 5 attempts per identity+IP, 60-second decay.
- "Keep me signed in" uses the remember token; sessions are DB-backed.
- Guests hitting `/tmgamb/*` are redirected to `/tmgamb` and returned to their intended page.

### 2.3 Existing records

`php artisan tmg:backfill-ambassadors` is idempotent and chunked:

- Creates a missing ambassador for every existing `voter_record`.
- Hashes the record's `reference` as the password.
- Resolves the senatorial district from the LGA.
- Safe to re-run; never overwrites an existing account or password.

New registrations create the account automatically inside `RecordVoterSubmission`
(failures are logged, never block the registration).

### 2.4 Password management

- **Passwords are case-insensitive.** Every password is normalized to uppercase
  before hashing and before verification (`App\Support\AmbassadorPassword`). The
  login and change-password inputs mirror this by rendering and forcing uppercase
  as the ambassador types, so mixed case can never block a sign-in.
- The **TMG number is the initial password and the permanent reset code**.
- **Change password** lives on the profile page: current password, new password,
  confirmation, minimum 8 characters. The new value is uppercased before hashing.
- **Forgot password** (`/tmgamb/forgot-password`): enter a phone number or email.
  If the account has an email address on file, the password is reset to the TMG
  number and an email containing that number is sent to the registered address.
  The response is always generic (no account enumeration) and the endpoint is
  rate-limited (5 requests/minute per IP). The email is sent before the password
  is changed, so a mail failure leaves the existing password working.

---

## 3. Senatorial districts

- 36 states × 3 districts + FCT × 1 = **109 senatorial districts**.
- `senatorial_districts`: `state_id`, `name`, `slug`, unique `(state_id, slug)`.
- `lgas.senatorial_district_id` nullable FK; every one of the **774 LGAs** resolves
  to exactly one district.
- Source of truth: `Nigeria_polling_units.csv` (`state`, `lg`, `senatorial`), 176,846 rows,
  deduplicated to 774 LGA mappings. Normalized spellings (`deltal`→Delta,
  `ademawa`→Adamawa, `nassarawa`→Nasarawa, `fct`→Federal Capital Territory).
- Committed as a compact data file (`database/data/senatorial_districts.php`) and
  applied by `SenatorialDistrictSeeder`.

---

## 4. Data model

```
senatorial_districts  id, state_id FK, name, slug, timestamps
                      unique(state_id, slug)

lgas                  + senatorial_district_id FK nullable, index

ambassadors           id, voter_record_id FK unique, name, phone unique,
                      email nullable unique, password, photo_path,
                      state_id, lga_id, ward_id, polling_unit_id,
                      senatorial_district_id, terms_accepted_at,
                      notice_seen_at, last_login_at, remember_token, timestamps
                      index(senatorial_district_id), index(state_id)

settings              key primary, value text, timestamps

community_links       id, platform(telegram|whatsapp), scope_type(global|state|district),
                      scope_id nullable, label, url, is_active, timestamps
                      index(platform, scope_type, scope_id)

nominations           id, public_id uuid unique, ambassador_id FK unique,
                      state_id, senatorial_district_id, statement nullable,
                      status(active|withdrawn), votes_count unsigned int default 0,
                      nominated_by nullable FK ambassadors, timestamps
                      index(district_id, votes_count), index(state_id, votes_count)

nomination_votes      id, nomination_id FK, voter_id FK ambassadors,
                      senatorial_district_id FK, timestamps
                      unique(voter_id, senatorial_district_id)  -- one vote per zone
                      unique(nomination_id, voter_id)

nomination_invitations id, public_id uuid, nominee_id FK, inviter_id FK,
                      senatorial_district_id, status(pending|accepted|rejected),
                      message nullable, responded_at nullable, timestamps
                      unique(nominee_id, inviter_id)
```

### 4.1 Voting invariants

- **One vote per ambassador per senatorial district**, enforced by a DB unique index.
- **One nomination per ambassador** (`ambassador_id` unique).
- A vote is cast in a transaction:
  1. `insertOrIgnore` into `nomination_votes`;
  2. if a row was inserted, `increment('nominations.votes_count')`;
  3. otherwise the vote is a no-op (`already_voted`).
- Self-nomination creates the nomination **and** the nominee's own vote row → `votes_count = 1`.
- Invitation acceptance creates the nomination and records the inviter's vote.
- You cannot vote for yourself, for a nominee outside your district, or for an
  inactive nomination.
- Leaderboard reads use the denormalized `votes_count` with covering indexes and a
  short cache (10s), busted on every successful vote.

---

## 5. Ambassador interface

### 5.1 Design language

Built on the existing Tailwind 4 design tokens (`resources/css/app.css`), with the
register page's glass/neomorphic mix, red/blue accents, dark + light themes.

- Login and app share an abstract backdrop: layered aurora orbs, blueprint grid,
  noise overlay, floating rings, and a cool floating glass navbar.
- Theme toggle persists in `localStorage` (`tmg.theme`) and resolves before first paint.
- Fully responsive: desktop floating pill nav, mobile bottom tab bar.
- Accessible modals (focus trap, Escape, backdrop click) reuse `initModals()` patterns.

### 5.2 Pages

| Route | Purpose |
|---|---|
| `GET/POST /tmgamb` | Login (phone or email + password; TMG number initially) |
| `GET/POST /tmgamb/forgot-password` | Email the TMG number to the registered address |
| `POST /tmgamb/logout` | Sign out |
| `GET /tmgamb/dashboard` | Zone overview, stats, terms + notice modals, zone ambassador list/search |
| `GET /tmgamb/leaderboard` | Zone leaderboard (rank badges) + state top 5 |
| `GET /tmgamb/profile` | Read-only profile, TMG number reveal, photo upload, change password |
| `GET /tmgamb/nominee/{public_id}` | Nominee detail + vote action + social share |
| `POST /tmgamb/nominate` | Self-nomination |
| `POST /tmgamb/invite/{ambassador}` | Invite an ambassador to be nominated |
| `POST /tmgamb/invitations/{invitation}/accept|reject` | Respond to an invitation |
| `POST /tmgamb/vote/{nomination}` | Cast vote (terms required) |
| `POST /tmgamb/terms/accept`, `/notice/seen` | First-visit acknowledgements |
| `POST /tmgamb/profile/photo` | Compressed photo upload (Intervention Image) |

### 5.3 First-visit gates

- **Terms of service** modal on first dashboard visit until accepted (`terms_accepted_at`).
- **Information notice** modal driven by admin settings (`notice.enabled`, title, body).
  Shown until dismissed (`notice_seen_at`).
- Nominate and vote modals each require the terms checkbox before submitting.

### 5.4 Community

- Floating Telegram + WhatsApp buttons, resolved by scope precedence:
  ambassador's **district** → **state** → **global**.
- Links are admin-managed in `community_links`.

### 5.5 Sharing

Each nominee has a shareable link (`/tmgamb/nominee/{public_id}`) and share buttons
for WhatsApp, X/Twitter, Facebook, Telegram, and copy-link. Guests are sent through
login and returned to the nominee page.

---

## 6. Admin

- **Nomination leaderboard**: a national at-a-glance ranking of every nomination,
  with total votes, states/districts in view and the leading nominee. Filters by
  state, senatorial district, status (active/withdrawn/all) and a name/phone search.
  The top three are shown as a podium, followed by the full paginated ranking.
- **Leaderboard by state**: per-state totals (districts covered, nominations, votes,
  leading nominee) and, for a selected state, a district-by-district breakdown.
- **Nominee details**: full ambassador contact/geography, nomination metadata
  (who nominated, when, status, public ID), zone rank and the latest voters.
- **Senatorial zones**: states → districts → LGAs, with ambassador/nomination counts.
- **Settings**:
  - information notice on/off + title + body;
  - terms of service title + body;
  - community links CRUD (global/state/district × Telegram/WhatsApp).
- Admin nav extended; existing console styling reused.

---

## 7. Performance & robustness

- Denormalized `votes_count`; no aggregate `COUNT(*)` on the hot path.
- Unique indexes guarantee correctness under concurrency; `insertOrIgnore` + conditional
  increment keeps a vote to two indexed writes.
- Leaderboard cache 10s (busted per district on vote) so 100k readers do not hammer MySQL.
- Login and vote routes rate-limited.
- Photo pipeline: Intervention Image, square cover crop to 600×600, WebP quality 82,
  single file per ambassador (replaced on re-upload).
- Registration never fails because ambassador provisioning failed (logged; backfill fixes).
- Tests: auth, backfill, zone scoping, nomination, invitation, duplicate vote race,
  cross-zone rejection, leaderboard ordering, settings/notice, photo upload.

---

## 8. Implementation phases

1. Schema + models + senatorial data/seeder.
2. Ambassador account provisioning + backfill command + registration hook.
3. Auth guard, login screen, middleware redirects.
4. App layout, dashboard, zone list/search.
5. Nominations, invitations, voting, leaderboards.
6. Profile + photo pipeline.
7. Terms/notice/settings + community links.
8. Admin pages.
9. Tests, Pint, asset build, backfill run, verification.

---

## 9. Acceptance criteria

- An existing voter record can sign in at `/tmgamb` with phone/email + TMG number.
- New registrations can sign in immediately with the same credentials.
- All 774 LGAs are mapped to 109 senatorial districts.
- A signed-in ambassador only sees their own district's ambassadors and leaderboard.
- The state top 5 is visible; zone ranks 1–3 carry badges.
- A second vote in the same zone is rejected at the database level.
- Concurrent votes never lose an increment and never double-count.
- Information notice obeys the admin toggle/message.
- Telegram/WhatsApp links resolve per district → state → global.
- Dark/light theme works on every ambassador page.
- Passwords are case-insensitive and forced to uppercase in the UI.
- An ambassador can change their password and sign in with the new one.
- Forgot password emails the TMG number to the registered address and restores it
  as the password; accounts without an email are left untouched.
- Admin can see a national nomination leaderboard with state/district/status/search
  filters, a per-state breakdown, and full nominee details with recent voters.
- Tests and Pint pass; assets build.

---

## 10. Runbook

```bash
php artisan migrate
php artisan db:seed --class=SenatorialDistrictSeeder
php artisan db:seed --class=SettingSeeder
php artisan tmg:backfill-ambassadors     # creates logins for existing records
php artisan storage:link                 # profile photos
npm run build
```

The backfill is idempotent and hashes each voter record's TMG number as that
ambassador's password. Re-run it safely after importing new records. New
registrations are provisioned automatically.

Admin console (`config('admin.path')`, default `/ops-7f3a9c2e`):

- **Senatorial zones** — states → districts → LGAs, with counts.
- **Settings** — information notice toggle/message, terms text, community links
  (general / state / district × Telegram / WhatsApp).

Community links start empty. Add the real Telegram and WhatsApp URLs in the
admin settings; the floating buttons appear automatically once a link resolves.
The information notice is off by default.

