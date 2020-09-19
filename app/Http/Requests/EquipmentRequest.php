<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipmentRequest extends FormRequest
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
            'category_id' => 'required|numeric',
            'name' => 'required',
            'en_name' => 'required',
            'first_cat' => 'integer|numeric',
            'second_cat' => 'numeric',
            'en_cat_type' => 'required',
            'information' => 'required|string',
            'workouts' => 'required|string',
            'general_des' => 'required|string',
            'en_general_des' => 'required|string',
            'morabiman_des' => 'required|string',
            'en_morabiman_des' => 'required|string'
        ];
    }
}
