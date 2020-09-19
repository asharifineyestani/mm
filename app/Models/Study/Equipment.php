<?php

namespace App\Models\Study;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    public $timestamps = false;
    protected $fillable = ['category_id','name','information','workouts','first_cat','second_cat','categoriy_id','general_des','morabiman_des','en_name','en_general_des','en_morabiman_des','en_cat_type'];

	public function addables()
	{
		return $this->morphMany('App\Models\Addable', 'addable');
	}

}
