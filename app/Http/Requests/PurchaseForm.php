<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

class PurchaseForm extends FormRequest
{
    public $product;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->getProduct();

        return ! auth()->user()->purchasesOf($this->product)->exists();
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('You have already purchased this item. To view it, please head to My Downloads under the main menu.');
    }

    public function getProduct()
    {
        $model = $this->route('model');
        $reference = $this->route('reference');

        $this->product = $model::bySlug($reference);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
