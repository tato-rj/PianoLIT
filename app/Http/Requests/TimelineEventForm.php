<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelineEventForm extends FormRequest
{
    public function authorize()
    {
        return auth('admin')->check();
    }

    public function rules()
    {
        return [
            'year' => 'required|integer|between:1,9999',
            'event_date' => 'nullable|date_format:Y-m-d',
            'title' => 'required|string|max:255', 'description' => 'required|string|max:2000',
            'image_url' => 'nullable|url|starts_with:https://|max:2000',
            'image_source_url' => 'nullable|url|starts_with:https://|max:2000',
            'image_credit' => 'nullable|string|max:2000', 'image_license' => 'nullable|string|max:255',
            'image_license_url' => 'nullable|url|starts_with:https://|max:2000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('event_date') || $validator->errors()->has('year')) return;
            if ($this->event_date && (int) substr($this->event_date, 0, 4) !== (int) $this->year) {
                $validator->errors()->add('event_date', 'The date must be in the event year. Leave it blank for a year-only event.');
            }
        });
    }
}
