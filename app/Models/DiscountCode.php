<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    //
    protected $table = 'discount_codes';

    protected $fillable = ['coach_id', 'code', 'percent', 'expired_at'];


    public function getPercentDiscountFromCode($codeName = null, $coachId = null)
    {
        $result = new \stdClass();
        if ($codeName && $coachId){
            $record =$this
                ->where('coach_id', $coachId)
                ->where('code', $codeName)
                ->where('expired_at', '>', Carbon::now())
                ->first();
            if ($record) {
                $result->percent = $record->percent;
                return $result;
            }

        }
        else  {

            $result->percent = 0;
            return $result;
        }
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id', 'id')->select('id', 'first_name', 'last_name');
    }

    public function formatExpiredAt($format = 'j F Y')
    {
        return Verta::createTimestamp(strtotime($this->expired_at))->format($format);
    }
}
