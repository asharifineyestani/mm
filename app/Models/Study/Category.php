<?php

namespace App\Models\Study;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];

    public function workouts(){
        return $this->hasMany(Workout::class);
    }
}
