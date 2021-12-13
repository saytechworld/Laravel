<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'name' => 'required|max:50',
            'email' => 'required|email|max:50|unique:users,email,'.auth()->id(),
            'user_details' => 'required|array|min:1',
            'user_details.gender' => 'required|in:M,F',
            'user_details.dob' => 'required|date_format:m/d/Y',
            'user_details.country_id' => 'required|exists:countries,id',
            'user_details.state_id' => 'nullable|exists:states,id,country_id,'.$this->request->get('user_details')['country_id'],
            'user_details.city_id' => 'nullable|exists:cities,id,state_id,'.$this->request->get('user_details')['state_id'],
            //'zip_code' => 'required|exists:zipcodes,zip_code,country_id,'.$this->request->get('user_details')['country_id'],
            'user_details.mobile_code_id' => 'required|exists:countries,id',
            'user_details.mobile' => 'required|regex:/^\d{0,20}$/',
            'user_details.about' => 'required|max:500',/*
            'user_details.image' => 'nullable|mimes:jpg,png,jpeg|max:204800',*/

        ];
    }

    

    public function messages()
    {
        return [
           
            'user_details.required' => 'The user detail fields are required.',
            'user_details.array' => 'The user detail fields not in valid format.',
            'user_details.gender.required' => 'The gender field is required.',
            'user_details.gender.in' => 'The gender field is not valid.',
            'user_details.dob.required' => 'The date of birth field is required.',
            'user_details.country_id.required' => 'The country field is required.',
            'user_details.country_id.exists' => 'The country field is not valid.',
            'user_details.state_id.exists' => 'The state field is not belong to selected country.',
            'user_details.city_id.exists' => 'The city field is not  belong to selected state.',
            'user_details.mobile.required' => 'The mobile field is required.',
            'user_details.mobile.regex' => 'The mobile field is not valid.',
            'user_details.mobile.unique' => 'The mobile has already been taken.',
        ];
    }

}
