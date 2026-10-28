<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewsForm extends FormRequest
{
    protected $errorBag = 'review';

    public $product;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $model = $this->route('model');
        abort_unless(in_array($model, [\App\Shop\eBook::class, \App\Shop\eScore::class, \App\Infograph\Infograph::class], true), 404);
        $this->product = $model::findOrFail($this->route('id'));

        return auth()->check() && ! auth()->user()->reviewed($this->product);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|string|max:50',
            'content' => 'nullable|string|max:220',
            'reviewer' => 'nullable|string|max:50'
        ];
    }
}
