<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAmbassadorPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('ambassador') !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.(int) config('ambassadors.photo.max_kilobytes', 5120),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.image' => 'Choose an image file (JPG, PNG or WebP).',
            'photo.max' => 'The photo must be 5 MB or smaller.',
        ];
    }
}
