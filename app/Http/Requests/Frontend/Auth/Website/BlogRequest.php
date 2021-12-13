<?php

namespace App\Http\Requests\Frontend\Auth\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Response;


class BlogRequest extends FormRequest
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
            "featured_image"    => "required|url",
            //"publish_date"    => "nullable|date_format:Y-m-d g:i A",
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
