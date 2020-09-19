<?php

namespace App\Models;

use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Database\Eloquent\Model;
use App\User;
class Request extends Model
{
    protected $casts = [
        'questions' => 'array',
        'plans' => 'array'
    ];

    public $timestamps = false;

    protected $fillable = ["user_id", "coach_id", "total_price",
        "payment_status", "payment_type", "program_status", "questions",
        "plans", "description" , "tracking_code","discount_percent","created_at","updated_at"];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coach()
    {
        return $this->belongsTo(User::class,'coach_id','id');
    }

//    public function setQuestionsAttribute($options)
//    {
//        $this->attributes['questions'] = json_encode($options);
//    }


//    public function getQuestionsAttribute($model, $key, $value, $attributes)
//    {
//        return json_decode($value, true);
//    }


//    public function setQuestionsAttribute($value)
//    {
//        $this->attributes['questions'] =  json_encode($value);
//    }

	public function formatDate($format = 'j F Y، H:i')
	{
		return Verta::createTimestamp(strtotime($this->created_at))->format($format);
    }

    public function setTrackingCodeAttribute()
    {
        $v = verta();
        $this->attributes['tracking_code'] = $v->format('ymdHis');
    }

	/*public function addables()
	{
		return $this->morphMany('App\Models\Addable', 'addable');
	}*/

    public function payment()
    {
        return $this->hasOne(Payment::class)->latest();
    }
}
