<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $table = 'sms_logs';
    //public $timestamps = true;


    public static function validFromToken($code,$mobile=null)
    {
        $valid=self::select('*');
        //->where('created_at', '>', Carbon::parse('-15 minutes')) #todo uncomment
        if($code != null)
            $valid=$valid->where('code', $code);

        if($mobile != null)
            $valid=$valid->where('mobile', $mobile);

        $valid=$valid->firstOrFail();
        return $valid;
    }

    public static function validFromLog($code,$mobile=null)
    {
        $valid=self::select('*')
        ->where('created_at', '>', Carbon::parse('-15 minutes')); #todo uncomment

        if($code != null)
            $valid=$valid->where('code', $code);

        if($mobile != null)
            $valid=$valid->where('mobile', $mobile);

        $valid=$valid->first();

        if($valid)
            return true;
        else
            return false;
    }




    public static function setLog($mobile,$ip,$device_id)
    {
        return self::insert([
            [
                'mobile' => $mobile,
                'ip' => $ip,
                'code' => rand(100,999),
                'device_id' => $device_id,
                'created_at' => Carbon::now(),
                'updated_At' => Carbon::now(),

            ]
        ]);

    }
}
