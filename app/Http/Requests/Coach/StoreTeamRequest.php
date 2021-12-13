<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreTeamRequest extends FormRequest
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
            'title'       => 'required|max:50',
            'description' => 'nullable|max:1000',
            'member'   => 'nullable|array',
            'member.*'   => 'nullable|exists:users,id,status,1,confirmed,1,deleted_status,0',
        ];
    }

    public function messages()
    {
        return [
            'member.*.exists'   => 'Member is invalid',
        ];
    }

    public function failedValidation(Validator $validator) { 
        return $this->validator = $validator;
    }

}
