<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Body extends Model
{
    public $timestamps = false;
    protected $fillable = ["user_id", "height", "weight", "neck", "arm_in_normal", "arm_in_contraction", "forearm", "wrist", "chest", "waist", "hip", "thigh", "shin", "ankle"];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function addables()
    {
        return $this->morphMany('App\Models\Addable', 'addable');
    }


    public function getHeightAttribute($value)
    {
        return floatval($value);
    }

    public function getWeightAttribute($value)
    {
        return floatval($value);
    }

    public function getNeckAttribute($value)
    {
        return floatval($value);
    }

    public function getArmInNormalAttribute($value)
    {
        return floatval($value);
    }

    public function getArmInContractionAttribute($value)
    {
        return floatval($value);
    }

    public function getForearmAttribute($value)
    {
        return floatval($value);
    }

    public function getWristAttribute($value)
    {
        return floatval($value);
    }

    public function getChestAttribute($value)
    {
        return floatval($value);
    }

    public function getWaistAttribute($value)
    {
        return floatval($value);
    }

    public function getHipAttribute($value)
    {
        return floatval($value);
    }


    public function getThighAttribute($value)
    {
        return floatval($value);
    }

    public function getShinAttribute($value)
    {
        return floatval($value);
    }

    public function getAnkleAttribute($value)
    {
        return floatval($value);
    }
}
