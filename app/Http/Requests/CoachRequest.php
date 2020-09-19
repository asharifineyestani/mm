<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoachRequest extends FormRequest
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
            'first_name'=> 'required | string',
            'last_name'=> 'required | string',
            'password' => 'required | string',
            'email' => 'required | string | email | unique:users,email',
            'gender'=> 'required',
            'mobile' => 'required | string | unique:users,mobile',
            'coach.program_per_day' => 'required',
        ];

    }
}
