<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditLog extends Model
{
    protected $table = 'credit_logs';

    protected $fillable = [
        'credit_id', 'amount', 'tracking_code', 'type', 'accepted', 'description' , 'details' , 'related_id','status'
    ];

    protected $casts = [
        'amount' => 'float',
        'details' => 'json'
    ];


    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }


    public function getAmountWithSignAttribute()
    {
        return in_array($this->type, ['deposit', 'refund'])
            ? '+' . $this->amount
            : '-' . $this->amount;
    }

}
