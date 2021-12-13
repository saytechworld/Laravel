<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequest extends FormRequest
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
            'title'       => 'required|max:50',
            'description' => 'nullable|max:1000',
            'file_name'   => 'mimes:jpg,png,jpeg|max:204800|required',
            'user_folder_id'   => 'nullable|exists:user_folders,id,folder_type,2,user_id,'.auth()->id(),
            'video_tag'   => 'nullable|array|min:1',
            'video_tag.*'   => 'nullable|exists:tags,id',
            //'file_name'          =>'mimes:mpeg,ogg,mp4,webm,3gp,mov,flv,avi,wmv,ts|max:1024|required'];
        ];
    }

}
