@props([
    'name',
    'label',
    'placeholder' => 'Select an option',
    'hint' => null,
    'disabled' => false,
    'required' => true,
])

@php
    $triggerId = 'combo-'.$name.'-trigger';
    $listId = 'combo-'.$name.'-list';
@endphp

<div data-combobox data-name="{{ $name }}" data-disabled="{{ $disabled ? 'true' : 'false' }}">
    <label class="field-label" for="{{ $triggerId }}">
        {{ $label }}
        @if ($required)
            <span class="req" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="combo">
        <button
            type="button"
            id="{{ $triggerId }}"
            class="combo-trigger neo-inset"
            data-combo-trigger
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="{{ $listId }}"
            @disabled($disabled)
        >
            <span class="combo-value" data-combo-value data-empty="true" data-placeholder="{{ $placeholder }}">{{ $placeholder }}</span>
            <svg class="combo-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        <div class="combo-panel glass" data-combo-panel hidden>
            <div class="combo-search">
                <input
                    type="text"
                    data-combo-search
                    placeholder="Search {{ strtolower($label) }}…"
                    aria-label="Search {{ $label }}"
                    autocomplete="off"
                    spellcheck="false"
                />
            </div>
            <ul class="combo-list" id="{{ $listId }}" role="listbox" aria-label="{{ $label }}" data-combo-list></ul>
            <p class="combo-empty" data-combo-empty hidden>No matches found.</p>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" value="" data-combo-input />

    @if ($hint)
        <p class="field-hint">{{ $hint }}</p>
    @endif

    <p class="field-error" data-error-for="{{ $name }}" hidden></p>
</div>
