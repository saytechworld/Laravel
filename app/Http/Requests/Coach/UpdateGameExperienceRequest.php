<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameExperienceRequest extends FormRequest
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
            'user_experience'   => 'required|regex:/^[0-9]\d*(\d{1,2})?$/',
            'select_games' => 'required|array|min:1',
            'select_games.*' => 'required|exists:games,id',
            'game_skill' => 'required|array|min:1',
            'game_skill.*.game_id' => 'required|exists:games,id',
            'game_skill.*.skill_id' => 'required|array|min:1',
            'game_skill.*.skill_id.*' => 'required|exists:skills,id',
            'select_languages' => 'required|array|min:1',
            'select_languages.*' => 'required|exists:languages,id',
        ];
    }

    public function messages()
    {
        return [
           
            'select_games.required' => "The game is required.",
            'select_games.array' => "The game format not valid.",
            'select_games.*.required' => "The game is required.",
            'select_games.*.exists' => "The game is invalid.",
            'game_skill.required' => "The skill is required.",
            'game_skill.array' => "The skill format not valid.",
            'game_skill.*.game_id.required' => 'The game is required.',
            'game_skill.*.game_id.exists' => 'The game is invalid.',
            'game_skill.*.skill_id.required' => 'The skill is required.',
            'game_skill.*.skill_id.array' => 'The skill format not valid.',
            'game_skill.*.skill_id.*.required' => 'The skill is required.',
            'game_skill.*.skill_id.*.exists' => 'The skill is invalid.',

            'select_languages.required' => "The language is required.",
            'select_languages.array' => "The language format not valid.",
            'select_languages.*.required' => "The language is required.",
            'select_languages.*.exists' => "The language is invalid.",
            'user_experience.required' => 'The experience is required.',
            'user_experience.regex' => 'The experience is not valid.',
        ];
    }

}
