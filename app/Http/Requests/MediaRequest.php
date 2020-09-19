<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class MediaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }


    public function rules()
    {

        return [
            'pictures.*' => 'array',
            'videos.*' => 'array',
            'pictures.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8000000',
            'videos.*' => 'mimetypes:video/avi,video/mp4,video/mpeg,video/quicktime|max:18000000',

        ];
    }


    public function messages()
    {
        return [
            'pictures.array' => 'The :attribute is not a valid array',
            'videos.array' => 'The :attribute is not a valid array',
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
