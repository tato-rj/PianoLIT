<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use GuzzleHttp\Client;

class Recaptcha implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $params = [
            'secret' => config('services.recaptcha.secret'),
            'response' => $value,
            'remoteip' => request()->ip()
        ];

        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', ['form_params' => $params])->getBody();

        return json_decode($response)->success;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The reCaptcha has failed. If this problem persists, please let us know at contact@pianolit.com.';
    }
}
