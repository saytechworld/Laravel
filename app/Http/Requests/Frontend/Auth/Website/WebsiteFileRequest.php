<?php

namespace App\Http\Requests\Frontend\Auth\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Response;


class WebsiteFileRequest extends FormRequest
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
            "file"    => "required|array|min:1",
            "file.*"    => "required|image|mimes:jpeg,jpg,png|max:5120",
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
