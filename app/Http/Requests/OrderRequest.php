<?php

namespace App\Http\Requests;

use App\Rules\ArrayRule;
use App\Rules\MediaPath;
use App\Rules\PlanRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class OrderRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'coach_id' => 'required|exists:users,id',
            'payment_type' => 'required | in:OTHER,ONLINE,CREDIT',
            'body' => 'required | array',
            'pictures' => 'array',
            'plans' => ['required', 'array' , new PlanRule($this->get('coach_id'))],
            'questions' => ['array'],
            'media_paths.*' => new MediaPath()
        ];
    }


    public function messages()
    {
        return [
            'description.required' => 'Description is required!',
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
