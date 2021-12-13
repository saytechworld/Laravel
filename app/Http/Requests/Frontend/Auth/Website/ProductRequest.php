<?php

namespace App\Http\Requests\Frontend\Auth\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Response;


class ProductRequest extends FormRequest
{
    public $validator; 
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
            "title"    => "required|min:2|max:50",
            'category_id' => 'required|array|min:1',
            'category_id.*' => 'required|exists:webstore_categories,id'
        ];
    }

    public function wantsJson()
    {
        return true;
    }

    public function failedValidation(Validator $validator) { 
        $this->validator = $validator;
    }
}
