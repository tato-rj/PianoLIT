<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PianistForm extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->errorBag = $this->route('pianist') ? 'default' : 'pianistCreate';
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $pianist = $this->route('pianist');
        return $this->user('admin') && (!$pianist || $this->user('admin')->can('update', $pianist));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:140', Rule::unique('pianists')->ignore(optional($this->route('pianist'))->id)],
            'biography' => 'required|string',
            'cover' => [$this->route('pianist') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
            'country_id' => 'required|integer|exists:countries,id',
            'itunes_id' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'date_of_death' => 'nullable|date|after_or_equal:date_of_birth',
            'editor_section' => 'nullable|string|in:pianist-profile,pianist-biography,pianist-music',
        ];
    }

    public function attributes()
    {
        return ['country_id' => 'nationality', 'itunes_id' => 'Apple Music artist ID', 'cover' => 'portrait'];
    }
}
