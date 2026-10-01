@php
    $category = \App\Enums\VolunteerCategory::tryFrom((string) $record->volunteer_category)?->label();
    $location = collect([$record->ward?->name, $record->lga?->name, $record->state?->name])->filter()->implode(' / ');
@endphp
TINUBU MUST GO — TMG AMBASSADOR PROGRAMME

Hello {{ $record->full_name }},

Thank you for stepping forward. Your registration is confirmed and you are now
part of the TMG Ambassador Programme — the volunteers who organise at
polling-unit and ward level to protect the vote and turnout our people.

YOUR MEMBER REFERENCE: {{ $record->reference }}

Keep this reference — it identifies you at polling-unit and ward level.

YOUR REGISTRATION
@if ($location !== '')- Ward / LGA / State: {{ $location }}
@endif
@if ($record->pollingUnit?->name)- Polling unit: {{ $record->pollingUnit->name }}
@endif
@if ($category)- Volunteer category: {{ $category }}
@endif

AS A TMG AMBASSADOR, YOU WILL:
- Support TMG's chosen candidate, Atiku Abubakar.
- Mobilise and campaign at polling-unit and ward level.
- Recruit and organise other TMG volunteers.
- Encourage eligible voters to turn out and vote.

Open the mission space: {{ url('/') }}

You received this because you registered as a TMG Ambassador. If this was not
you, please ignore this email.

Privacy requests: privacy@tinubumustgo.org
