<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addable extends Model
{
    protected $fillable = ['addable_id','media_path','category','addable_type'];

	public function addable()
	{
		return $this->morphTo();
	}
}
