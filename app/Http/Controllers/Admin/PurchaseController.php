<?php

namespace App\Http\Controllers\Admin;


use App\Helpers\Sh4Helper;
use App\Http\Controllers\Controller;
use App\Models\CreditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        //
        //$recors = CreditLog::select(
        //    'users.first_name as user_first_name', 'users.last_name as user_last_name'
        //    ,'credit_logs.amount as price', 'credit_logs.id as id', 'credit_logs.details', 'credit_logs.created_at as credit_created_at', 'credit_logs.type as credit_type', 'credit_logs.tracking_code'
        //    ,'requests.payment_status',   'requests.coach_id' , 'requests.user_id','requests.id as request_id'
        //    ,'coaches.first_name as coach_first_name', 'coaches.last_name as coach_last_name'
        //)
        //    ->leftJoin('credits', 'credit_logs.credit_id', '=', 'credits.id')
        //    ->where('type', 'WITHDRAW_BUY')
//            ->orWhere('type', 'DEPOSIT_INCREASE_CREDIT')
//            ->orWhere('type', 'DEPOSIT_REWARD')
//            ->leftJoin('requests', 'requests.id', '=', 'credit_logs.related_id')
//            ->leftJoin('users', 'users.id', '=', 'requests.user_id')
//            ->leftJoin('users as coaches', 'coaches.id', '=', 'requests.coach_id')
//            ->get();


//        return $recors;
        return view('admin.purchases.index');
    }

	public function getPurchases(Request $request)
	{
		$query = CreditLog::leftJoin('credits', 'credit_logs.credit_id', '=', 'credits.id')
			->leftJoin('requests', 'requests.id', '=', 'credit_logs.related_id')
			->leftJoin('users', 'users.id', '=', 'requests.user_id')
			->leftJoin('users as coaches', 'coaches.id', '=', 'requests.coach_id')
			->select(DB::raw(
				'credit_logs.id as id,' .
				'CONCAT(users.first_name, " ", users.last_name) as name,' .
				'users.id as user_id,' .
				'credit_logs.amount as price,' .
				'credit_logs.details,' .
				'credit_logs.created_at as credit_created_at,' .
				'credit_logs.type as credit_type,' .
				'credit_logs.tracking_code,' .
				'requests.payment_status,' .
				'requests.coach_id,' .
				'requests.user_id,' .
				'requests.id as request_id,' .
				'CONCAT(coaches.first_name, " ", coaches.last_name) as coach,' .
				'coaches.id as coach_id'
			))
			->where('type', 'WITHDRAW_BUY');
		

		$results = datatables($query)
			->editColumn('name', function ($creditLog){
				return '<a href="' . route("admin.users.edit", $creditLog->user_id) . '">' . $creditLog->name . '</a>';
			})->editColumn('coach', function ($creditLog){
				return '<a href="' . route("admin.coaches.edit", $creditLog->coach_id) . '">' . $creditLog->coach . '</a>';
			})
			->editColumn('credit_created_at', function ($creditLog){
				return  '<span class="small">' . Sh4Helper::formatPersianDate($creditLog->credit_created_at, 'j F Y') . '</span>';
			})
			->editColumn('payment_status', function ($creditLog){
				return __('mm.payment.status.' . $creditLog->payment_status);
			})
			->editColumn('credit_type', function ($creditLog){
				return __('mm.payment.type.' . $creditLog->credit_type);
			})
			->filterColumn('name', function ($query, $keyword) {
				$sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
				$query->whereRaw($sql, '%' . $keyword . '%');
			})
			->filterColumn('coach', function ($query, $keyword) {
				$sql = 'CONCAT(coaches.first_name, " ", coaches.last_name) like ?';
				$query->whereRaw($sql, '%' . $keyword . '%');
			})
			->filterColumn('credit_created_at', function ($query, $keyword) {
				$date = array_map('intval', explode('/', $this->arabicNumbers($keyword)));
				$date_g = verta()->setDate($date[0], $date[1], $date[2])->formatGregorian('Y-m-d');
				$query->whereDate('credit_logs.created_at', $date_g);
			})
			->rawColumns(['name', 'credit_created_at', 'coach'])
			->make(true);

		return $results;
	}

    public function create()
    {
        //
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

	protected function arabicNumbers($string)
	{
		$indian1 = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
		$indian2 = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١', '٠'];
		$numbers = range(0, 9);
		$convertedIndian1 = str_replace($indian1, $numbers, $string);
		$englishNumbers = str_replace($indian2, $numbers, $convertedIndian1);

		return $englishNumbers;
	}
}
