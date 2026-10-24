@props(['ambassador' => null, 'size' => 'md'])

@if ($ambassador?->photo_path)
    <span {{ $attributes->class(['amb-avatar', 'amb-avatar-'.$size]) }}>
        <img src="{{ $ambassador->photoUrl() }}" alt="" loading="lazy" width="128" height="128">
    </span>
@else
    <span {{ $attributes->class(['amb-avatar', 'amb-avatar-'.$size]) }} aria-hidden="true">
        {{ $ambassador?->initials() ?? 'TMG' }}
    </span>
@endif
