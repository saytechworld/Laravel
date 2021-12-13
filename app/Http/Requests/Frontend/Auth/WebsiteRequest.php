<?php

namespace App\Http\Requests\Frontend\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class WebsiteRequest extends FormRequest
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
            'name' => 'required|max:50|unique:websites,name',
            'category_id' => 'required|exists:categories,id,parent_id,NULL',
        ];
    }

    


    public function failedValidation(Validator $validator) { 
        return $this->validator = $validator;
    }
}
