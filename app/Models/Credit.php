<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{

    public function transactions()
    {
        return $this->hasMany(CreditLog::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
