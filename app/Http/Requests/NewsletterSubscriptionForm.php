<?php

namespace App\Http\Requests;

class NewsletterSubscriptionForm extends SubscriptionForm
{
    protected function prepareForValidation()
    {
        if ($this->input('form_id') === 'shop-newsletter-subscription-form') {
            $this->errorBag = 'storefront';
        }
    }

    public function authorize()
    {
        try {
            return is_string($this->started_at) && parent::authorize();
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public function rules()
    {
        return ['email' => 'required|string|email|max:254'];
    }
}
