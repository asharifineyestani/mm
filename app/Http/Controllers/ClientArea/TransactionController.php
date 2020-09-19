<?php

namespace App\Http\Controllers\ClientArea;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index()
    {
        return view('clientArea.transactions.index');
    }

	public function getTransactions(Request $request)
	{
		$query = Payment::where('user_id', Auth::user()->id)
			->select('gateway_transactions.*');

		$results = datatables($query)
			->editColumn('port', function ($payment){
				return __('mm.paymentGateways')[$payment->port];
			})
			->editColumn('tracking_code', function ($payment){
				return $payment->tracking_code ?? '-';
			})
			->editColumn('created_at', function ($payment){
				return '<span class="small">' . $payment->formatCreatedAtDate() . '</span>';
			})
			->editColumn('payment_date', function ($payment){
				if ($payment->payment_date){
					return '<span class="small">' . $payment->formatPaymentDate() . '</span>';
				}else{
					return '-';
				}
			})
			->editColumn('status', function ($payment){
				return __('mm.payment.status')[$payment->status];
			})
			->rawColumns(['created_at', 'payment_date'])
			->make(true);

		return $results;
	}

    public function create()
    {

    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
