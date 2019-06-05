<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
                'amount'       => 'required|numeric',
                'method'       => 'required|string|in:cash,check,swipe,square,squarecash',
                'recorded_by'  => 'numeric|exists:users,id',
                'payable_type' => 'required|string',
                'payable_id'   => 'required|numeric',
               ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
