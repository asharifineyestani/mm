<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Received extends Model
{
    protected $table = 'emails_received';

    protected $fillable = ['files','checksum','email_number'];


    protected $casts = ['files' => 'json'];

}
