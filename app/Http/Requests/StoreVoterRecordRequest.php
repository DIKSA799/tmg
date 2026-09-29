<?php

namespace App\Http\Requests;

use App\Enums\AgeBand;
use App\Enums\Gender;
use App\Enums\PreferredChannel;
use App\Enums\PreferredLanguage;
use App\Enums\PvcStatus;
use App\Enums\RegisteredVoterStatus;
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

            'consent_to_contact' => ['required', 'boolean'],
            'consent_to_data' => ['required', 'accepted'],

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
            'phone.regex' => 'Enter a valid Nigerian phone number, for example 0800 000 0000.',
            'consent_to_data.accepted' => 'You must consent to data processing before we can store this record.',
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
            'consent_to_contact' => $this->boolean('consent_to_contact'),
            'consent_to_data' => $this->boolean('consent_to_data'),
            'preferred_language_other' => $this->input('preferred_language') === PreferredLanguage::Other->value
                ? $this->input('preferred_language_other')
                : null,
            'preferred_channel_other' => $this->input('preferred_channel') === PreferredChannel::Other->value
                ? $this->input('preferred_channel_other')
                : null,
        ]);
    }
}
