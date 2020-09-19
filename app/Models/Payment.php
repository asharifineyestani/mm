<?php

namespace App\Models;

use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'gateway_transactions';

	public function formatCreatedAtDate($format = 'j F Y، H:i')
	{
		return Verta::createTimestamp(strtotime($this->created_at))->format($format);
	}

	public function formatPaymentDate($format = 'j F Y، H:i')
	{
		return Verta::createTimestamp(strtotime($this->payment_date))->format($format);
	}
}
