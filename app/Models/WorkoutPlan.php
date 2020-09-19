<?php

namespace App\Models;

use App\Helpers\Sh4Helper;
use App\User;
use Illuminate\Database\Eloquent\Model;

class WorkoutPlan extends Model
{
    //
    protected $table = 'workout_plans';

    protected $casts = [
        'body' => 'array',
    ];

    protected $hidden = ['user_id','updated_at'];

    protected $fillable = [
        'user_id',
        'description',
        'body',
        'coach_id'
    ];

    public function user()
    {
        $this->belongsTo(User::class);
    }


    public function getCreatedAtAttribute($value)
    {

        return Sh4Helper::formatPersianDate($value);
    }
}
