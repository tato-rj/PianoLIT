<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Blog\PostPromotions;
use Illuminate\Validation\Rule;

class PostForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|min:4|max:120',
            'description' => 'required|string|max:238',
            'content' => 'required|string',
            'cover_image' => 'required|sometimes|mimes:jpeg,jpg',
            'cover_credits' => 'nullable|string|max:120',
            'promotion' => ['sometimes', 'required', 'string', Rule::in(array_keys(PostPromotions::CHOICES))],
            'topics' => 'nullable|array',
            'topics.*' => 'integer|distinct|exists:topics,id',
            'references' => 'nullable|array|max:50',
            'references.*' => 'nullable|string|max:2000',
        ];
    }

    protected function prepareForValidation()
    {
        if (is_array($this->input('references'))) {
            $references = array_map(fn ($reference) => is_string($reference) ? trim($reference) : $reference, $this->input('references'));
            $this->merge(['references' => array_filter($references, fn ($reference) => $reference !== '' && $reference !== null)]);
        }
    }
}
