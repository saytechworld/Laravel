<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class ContactUsRequest extends FormRequest
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
            echo "<pre>"; print_r($this->request->method());
        
        
        return [
           /* 'name' => 'required|max:50',
            'email' => 'required|email|max:50',
            'password' => 'required|min:6|max:12',
            'user_type' => 'required|in:coach,athelete', */
        ];
    }
}
