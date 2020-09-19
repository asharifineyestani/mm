<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Coach extends Model
{

	protected $primaryKey = 'user_id';
    protected $table = 'coach_fields';
    protected $fillable = ['address', 'order', 'national_code', 'education', 'about_gym', 'default_rank', 'total_rank', 'veto_score', 'admin_score',
                            'link', 'background', 'bank_card', 'program_per_day', 'visible', 'accept_new_student','user_id','emergency_message'];

    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function request()
    {
        return $this->hasOne(Request::class);
    }
}
