<?php

namespace App\Http\Requests;

use App\Enums\AgeBand;
use App\Enums\Gender;
use App\Enums\Occupation;
use App\Enums\PreferredChannel;
use App\Enums\PreferredLanguage;
use App\Enums\PvcStatus;
use App\Enums\RegisteredVoterStatus;
use App\Enums\VolunteerCategory;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\Ward;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoterRecordRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],

            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'age_band' => ['required', Rule::enum(AgeBand::class)],
            'phone' => ['required', 'string', 'regex:/^\+234[789]\d{9}$/'],
            'whatsapp' => ['nullable', 'string', 'regex:/^\+234[789]\d{9}$/'],
            'email' => ['nullable', 'email:rfc', 'max:120'],
            'volunteer_category' => ['required', Rule::enum(VolunteerCategory::class)],
            'occupation' => ['required', Rule::enum(Occupation::class)],
            'has_disability' => ['required', 'boolean'],

            'state_id' => ['required', 'integer', 'exists:states,id'],
            'lga_id' => ['required', 'integer', 'exists:lgas,id'],
            'ward_id' => ['required', 'integer', 'exists:wards,id'],
            'polling_unit_id' => ['required', 'integer', 'exists:polling_units,id'],

            'registered_voter_status' => ['required', Rule::enum(RegisteredVoterStatus::class)],
            'pvc_status' => ['required', Rule::enum(PvcStatus::class)],
            'preferred_language' => ['required', Rule::enum(PreferredLanguage::class)],
            'preferred_language_other' => ['nullable', 'string', 'max:60', 'required_if:preferred_language,other'],
            'preferred_channel' => ['required', Rule::enum(PreferredChannel::class)],
            'preferred_channel_other' => ['nullable', 'string', 'max:60', 'required_if:preferred_channel,other'],

            // The consent questions are optional: if the form omits them they are
            // stored as false instead of blocking the registration.
            'consent_to_contact' => ['boolean'],
            'consent_to_data' => ['boolean'],
            'pledge_accepted' => ['required', 'accepted'],

            'agent_id' => ['nullable', 'string', 'max:60'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'captured_at' => ['nullable', 'date'],
            'device' => ['nullable', 'array'],
            'device.device_id' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Nigerian mobile number, for example 0803 123 4567 or +234 803 123 4567.',
            'whatsapp.regex' => 'Enter a valid Nigerian WhatsApp number, for example 0803 123 4567 or +234 803 123 4567.',
            'pledge_accepted.accepted' => 'Please accept the TMG Ambassador pledge to continue.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['state_id', 'lga_id', 'ward_id', 'polling_unit_id'])) {
                    return;
                }

                $lga = Lga::query()->find($this->integer('lga_id'));
                $ward = Ward::query()->find($this->integer('ward_id'));
                $pollingUnit = PollingUnit::query()->find($this->integer('polling_unit_id'));

                if ($lga === null || $lga->state_id !== $this->integer('state_id')) {
                    $validator->errors()->add('lga_id', 'The selected LGA does not belong to the selected state.');
                }

                if ($ward === null || $ward->lga_id !== $lga?->id) {
                    $validator->errors()->add('ward_id', 'The selected ward does not belong to the selected LGA.');
                }

                if ($pollingUnit === null || $pollingUnit->ward_id !== $ward?->id) {
                    $validator->errors()->add('polling_unit_id', 'The selected polling unit does not belong to the selected ward.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'whatsapp' => PhoneNumber::normalize($this->input('whatsapp')),
            'email' => $this->filled('email') ? $this->input('email') : null,
            'has_disability' => $this->boolean('has_disability'),
            // Someone who is not a registered voter has no PVC to collect.
            'pvc_status' => $this->input('registered_voter_status') === RegisteredVoterStatus::No->value
                ? PvcStatus::NotCollected->value
                : $this->input('pvc_status'),
            'consent_to_contact' => $this->boolean('consent_to_contact'),
            'consent_to_data' => $this->boolean('consent_to_data'),
            'pledge_accepted' => $this->boolean('pledge_accepted'),
            'preferred_language_other' => $this->input('preferred_language') === PreferredLanguage::Other->value
                ? $this->input('preferred_language_other')
                : null,
            'preferred_channel_other' => $this->input('preferred_channel') === PreferredChannel::Other->value
                ? $this->input('preferred_channel_other')
                : null,
        ]);
    }
}
