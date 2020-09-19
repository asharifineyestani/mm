<?php

namespace App\Models\Study;

use Illuminate\Database\Eloquent\Model;

class Gym extends Model
{
    public $timestamps = false;
    protected $fillable = ['title','description','address','en_title','en_description','en_address','phone','instagram','start_time','end_time','sport_fields','en_sport_fields','club_facilities','en_club_facilities','lat','len','status'];

	public function addables()
	{
		return $this->morphMany('App\Models\Addable', 'addable');
	}
}
