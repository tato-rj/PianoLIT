<?php

namespace App\Http\Requests;

use App\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class SubscriptionForm extends FormRequest
{
    // protected $errorBag = 'subscription';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return ! $this->subscription_name && carbon($this->started_at)->lte(now()->subSeconds(3));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email'
        ];
    }
}
