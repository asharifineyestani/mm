<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public $timestamps = false;

    public function Province()
    {
        return $this->belongsTo(Country::class);
    }

    public function children()
    {
        return $this->hasMany(City::class,'parent_id');
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
