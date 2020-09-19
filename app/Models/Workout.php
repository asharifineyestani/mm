<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    //
    protected $table = 'm_workout';


    public function getPic1Attribute($value)
    {
        if ($value)
            return  '/uploads/workouts/' . $value;
    }

    public function getPic2Attribute($value)
    {
        if ($value)
            return  '/uploads/workouts/' . $value;
    }

    public function getPic3Attribute($value)
    {
        if ($value)
            return  '/uploads/workouts/' . $value;
    }

    public function getPic4Attribute($value)
    {
        if ($value)
            return  '/uploads/workouts/' . $value;
    }

    public function getVid1Attribute($value)
    {
        if ($value)
            return  '/uploads/workouts/' . $value;
    }

    public function getVid2Attribute($value)
    {
        if ($value)
            return  '/uploads/workouts/' . $value;
    }
}
