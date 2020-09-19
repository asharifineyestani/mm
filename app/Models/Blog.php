<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $table = 'posts';
    protected $fillable = ['user_id','category_id','title','body','slug','seo_title','excerpt','meta_description','meta_keywords','status','media_path'];
    public $timestamps = true;
}
