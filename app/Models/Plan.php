<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['title','type','order'];
    public $timestamps = false;

    public function prices()
    {
        return $this->hasMany(Price::class);
    }
}
