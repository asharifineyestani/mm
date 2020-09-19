<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    public $timestamps = false;
    protected $fillable = ["question_id", "body", "order"];
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
