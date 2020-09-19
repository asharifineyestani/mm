<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ads extends Model
{
    public $timestamps = false;
    protected $fillable = ['title','image','url','status'];
    //
}
