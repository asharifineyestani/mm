<?php

namespace App\Models;

use App\Helpers\Sh4Helper;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $table = 'emails_adapted';

    protected $casts = [
        'workout' => 'json',
        'analyze' => 'json',
        'nutrition' => 'json',
        'sounds' => 'json',
    ];

    protected $hidden = ['user_id','updated_at'];


    protected $fillable = [
        'user_id',
        'description',
        'workout',
        'coach_id',
        'analyze',
        'nutrition',
        'checksum',
        'log_id'
    ];

    public function user()
    {
        $this->belongsTo(User::class);
    }


    public function getCreatedAtAttribute($value)
    {

        return $value;
        return Sh4Helper::formatPersianDate($value);
    }

//    public function setAnalyzeAttribute($value)
//    {
//        $this->attributes['analyze'] = json_encode($value);
//    }
//
//    public function setWorkoutAttribute($value)
//    {
//        $this->attributes['workout'] = json_encode($value);
//    }
//
//    public function setNutritionAttribute($value)
//    {
//        $this->attributes['nutrition'] = json_encode($value);
//    }
}
