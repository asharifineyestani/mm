<?php

namespace App\Models;

use App\Helpers\Sh4Helper;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Adapted extends Model
{
    protected $table = 'emails_adapted';

    protected $casts = [
        'workout' => 'array',
        'analyze' => 'array',
        'nutrition' => 'array',
        'sounds' => 'array',
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
        'sounds',
        'log_id'
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
