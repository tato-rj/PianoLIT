<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Recaptcha;

class CrashCourseForm extends FormRequest
{
    protected $errorBag = 'crashcourse';

    protected function getRedirectUrl()
    {
        if ($course = $this->route('crashcourse')) {
            $url = route('crashcourses.show', $course);
            // Canonical popup submissions show errors on the course page.
            // Already-open legacy forms retain their original return host.
            if ($this->getHost() === parse_url($url, PHP_URL_HOST)) return $url;
        }

        return parent::getRedirectUrl();
    }

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
            'first_name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'g-recaptcha-response' => ['sometimes', new Recaptcha]
        ];
    }
}
