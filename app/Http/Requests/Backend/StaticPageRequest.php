<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Response;

class StaticPageRequest extends FormRequest
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
            'title'  => isset($this->staticpage->id) ? 'required|max:50|unique:static_pages,title,'.$this->staticpage->id : 'required|max:50|unique:static_pages,title',
            'image' => 'nullable|mimes:jpg,jpeg,png|max:10240',
            'description' => 'required'
        ];
    }


}
