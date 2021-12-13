<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Carbon\Carbon;

class UserEventRequest extends FormRequest
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
            'team'   => 'nullable|array|min:1',
            'team.*'   => 'nullable|exists:teams,id,user_id,'.auth()->id(),
            'category_id'   => 'nullable|exists:event_categories,id,user_id,'.auth()->id(),
            'event_date'    => 'required|date_format:Y/m/d H:i|after:'.Carbon::now()->format('Y/m/d H:i'),
            'event_color'    => 'required|exists:event_colors,id',
            'end_datetime'    => 'nullable|date_format:Y/m/d H:i|after_or_equal :'.$this->request->get('event_date'),
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
