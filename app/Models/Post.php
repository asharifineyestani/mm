<?php

namespace App\Models;

use App\Helpers\Sh4Helper;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    //

    public function scopePublished($builder)
    {
        return $builder->where('status' , '>' , 0);
    }


    public function getMediaPathAttribute($value)
    {

        return config('app.url') . $value;

    }
}
