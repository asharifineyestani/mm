<?php

namespace App\Http\Requests\API;

use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class UserRequest extends FormRequest
{

    public $id;

    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
            {
                return [
                    //            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8000000', /TODO be darkhaste farbod bardashte shod
                    'first_name' => 'required|string|min:3|max:30',
                    'last_name' => 'required|string|min:3|max:30',
                    'email' => 'required|email|unique:users',
                    'password' => 'required|string|min:3|max:65',
                    'mobile' => 'required|regex:/(09)[0-9]{9}/|unique:users',
                    'blood_group' => 'required | in:O+,A+,B+,AB+,O−,A−,B−,AB−',
                    'gender' => 'required | in:MALE,FEMALE',
                    'birth_day' => 'required',
//                    'birth_day' => 'required|date_format:Y-m-d|before:today',
                    'device_id' => 'required|string',
                ];
            }
            case 'PUT':
            {
                return [
                    'first_name' => 'required | string',
                    'last_name' => 'required | string',
                    'gender' => 'required | in:MALE,FEMALE',
                    'city_id' => 'required',
                    'blood_group' => 'required',
                    'email' => 'required | string | email | unique:users,email,' . $this->id,
                    'mobile' => 'required | string | unique:users,mobile,' . $this->id,
                    'avatar_path' => 'string', new MediaPath()
                ];
            }


            default:
                break;
        }
    }


    public function messages()
    {
        return [
            'description.required' => 'Email is required!',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = new JsonResponse([
            'status' => false,
            'errors' => $validator->errors(),
            'data' => null
        ]);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
