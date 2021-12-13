<?php

namespace App\Http\Requests\Frontend\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class WebsiteStoreRequest extends FormRequest
{
    public $validator;    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    

    public function wantsJson()
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
            'is_store' => 'required',
        ];
    }

    
    public function messages()
    {
        return [
            'is_store.required' => 'The store field is required.',
        ];
    }

    public function failedValidation(Validator $validator) { 
        return $this->validator = $validator;
    }
}
