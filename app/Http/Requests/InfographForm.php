<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InfographForm extends FormRequest
{
    protected $errorBag = 'infographic';

    protected function getRedirectUrl()
    {
        $filters = app(\App\Services\Admin\InfographicsTable::class)->filters(
            \Illuminate\Http\Request::create('/', 'GET', $this->query())
        );
        $parameters = \App\Support\AdminTableFilters::query($filters);
        return $this->isMethod('POST')
            ? route('admin.infographs.index', $parameters + ['create' => '1'])
            : route('admin.infographs.edit', ['infograph' => $this->route('infograph')] + $parameters);
    }
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
            'name' => 'required|string|min:4|max:120',
            'description' => 'required|string|min:4|max:238',
            'cover_image' => [$this->isMethod('POST') ? 'required' : 'nullable', 'image', 'mimes:jpeg,jpg', 'max:10240'],
            'topics' => 'nullable|array',
            'topics.*' => 'integer|distinct|exists:infograph_topics,id',
        ];
    }
}
