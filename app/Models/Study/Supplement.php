<?php

namespace App\Models\Study;

use Illuminate\Database\Eloquent\Model;

class Supplement extends Model
{
    public $timestamps = false;
    protected $fillable = ['category_id','name','information',
        'en_information',
        'first_cat',
        'second_cat',
        'categoriy_id',
        'general_des',
        'morabiman_des',
        'directions',
        'warning',
        'serving_size',
        'serving_per_container',
        'amount_per_serving',
        'other_ingredients',
        'descriptions',
        'company',
        'en_name',
        'en_general_des',
        'en_morabiman_des',
        'en_cat_type',
        'en_directions',
        'en_warning',
        'en_serving_size',
        'en_serving_per_container',
        'en_amount_per_serving',
        'en_other_ingredients',
        'en_descriptions',
        'en_company'];

	public function addables()
	{
		return $this->morphMany('App\Models\Addable', 'addable');
	}
}
