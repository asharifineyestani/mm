<?php

namespace App\Models\Study;

use Illuminate\Database\Eloquent\Model;

class Nutrient extends Model
{
    public $timestamps = false;
    protected $fillable = ['category_id','name','information','en_information','first_cat','second_cat','categoriy_id','general_des','morabiman_des','en_name','en_general_des','en_morabiman_des','en_cat_type','facts','vitamins','ingredients','recipes','en_vitamins','en_ingredients','en_recipes',
        'directions',
        'warning',
        'serving_size',
        'serving_per_container',
        'amount_per_serving',
        'company',
        'other_ingredients',
        'descriptions'];

	public function addables()
	{
		return $this->morphMany('App\Models\Addable', 'addable');
	}

}
