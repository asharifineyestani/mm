<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    //
    protected $table = 'emails_received';

    protected $appends = ['data','email_id'];

    protected $fillable = ['files', 'checksum', 'email_number'];


    protected $casts = ['files' => 'json'];


//    public function getDataAttribute($value)
//    {
//
//        if (@unserialize($value))
//            return unserialize($value);
//        elseif ($value == null)
//            return $value;
//        else
//            return 'wrong format';
//
//
//    }
//    public function setDataAttribute($value)
//    {
//        $this->attributes['data'] = serialize($value);
//    }


    public function getDataAttribute($value)
    {
        return $this->files;
    }

    public function getEmailIdAttribute($value)
    {
        return $this->email_number;
    }
}
