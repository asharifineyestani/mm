<?php

namespace App\Models\Study;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    public $timestamps = false;
    protected $fillable = ['category_id','name','first_cat','second_cat','general_des','morabiman_des','cat_type','mechanism','direction','main_equipment','more_equipment','des','prepration','execution','target','sinergist','stabilizers','lang','en_name','en_cat_type','en_mechanism','en_direction','en_main_equipment','en_more_equipment','en_des','en_prepration','en_execution','en_target','en_sinergist','en_stabilizers','en_general_des','en_morabiman_des'];

    public function category(){
        return $this->belongsTo(Category::class);
    }

	public function addables()
	{
		return $this->morphMany('App\Models\Addable', 'addable');
    }
}
