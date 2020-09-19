<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = ["body_id", "media_path", "type"];
    public function body()
    {
        return $this->belongsTo(Body::class);
    }
}
