<?php

namespace App\Http\Requests\Athelete;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Carbon\Carbon;

class UserEventUpdateRequest extends FormRequest
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
            'attendant'   => 'nullable|array|min:1',
            'attendant.*'   => 'nullable|exists:users,id,status,1,confirmed,1,deleted_status,0',
            'event_date'    => 'required|date_format:Y/m/d H:i',
            'event_color'    => 'required|exists:event_colors,id',
        ];
    }

    public function messages()
    {   
        return [
            'title'       => 'required|max:50',
            'attendant'   => 'required|array|min:1',
            'attendant.*.required'   => 'The attendant is required.',
            'attendant.*.exists'   => 'The attendant is invalid.',
        ];
    }

    public function failedValidation(Validator $validator) { 
        return $this->validator = $validator;
    }

}
