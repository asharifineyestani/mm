<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Price extends Model
{
	protected $primaryKey = null;
	public $incrementing = false;
    protected $fillable = ['user_id','plan_id','price'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
