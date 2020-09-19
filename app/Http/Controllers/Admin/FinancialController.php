<?php

namespace App\Http\Controllers\Admin;

use App\Models\CreditLog;
use App\User;
use Carbon\Carbon;
use Carbon\Laravel\ServiceProvider;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FinancialController extends Controller
{
    /*
     *
     * written by aeini
     * updated at 1398/09/10
     */
    public function index()
    {
        $starttime = Carbon::now()->subYear(1);
        $finishtime = Carbon::now();
        $record=\App\Models\Request::select(
            'requests.id as request_id as Id',
            'requests.total_price as Price',
            'requests.discount_percent as Discount',
            'requests.payment_status',
            'requests.payment_type',
            'requests.tracking_code as Code',
            'requests.created_at as created_at',
            'users.first_name as user_first_name',
            'users.last_name as user_last_name',
            'users.introduction_method',
            'coaches.first_name as coach_first_name',
            'coaches.last_name as coach_last_name',
            'credit_logs.tracking_code as codeCredit',
            'gateway_transactions.ref_id'
        )
            ->leftJoin('users', 'requests.user_id', '=', 'users.id')
            ->leftJoin('users as coaches', 'coaches.id', '=', 'requests.coach_id')
            ->leftJoin('credit_logs', 'requests.id', '=', 'credit_logs.related_id')
            ->leftJoin('gateway_transactions', 'credit_logs.related_id', '=', 'gateway_transactions.request_id')
            ->leftJoin('credits', 'credit_logs.credit_id', '=', 'credits.id')
            ->get();

        $coaches = User::roleIs('coach')->get();
        $users = User::roleIs('user')->get();
        return view('admin.financial.index')->with([
            'payments' => $record,
            'coaches' => $coaches,
            'users' => $users,

        ]);
    }
    /*
      *
      * written by aeini
      * updated at 1398/09/10
      */
    public function table()
    {
        $config = new \stdClass();
        $config->routeName = 'admin.financial';
        $config->table = 'financial';
        $config->buttons = ['edit' => false, 'destroy' => false];
        $starttime = Carbon::now()->subYear(1);
        $finishtime = Carbon::now();
        $query = \App\Models\Request::leftJoin('users', 'requests.user_id', '=', 'users.id')
            ->leftJoin('users as coaches', 'coaches.id', '=', 'requests.coach_id')
            ->leftJoin('gateway_transactions', 'gateway_transactions.request_id', '=', 'requests.id')
           /* ->where(function ($q) {
                $q->where('gateway_transactions.tracking_code','!=','');
            })*/
            ->select(['requests.id as id',
                'users.introduction_method',
                'requests.created_at',
                'requests.total_price',
                'requests.description as manual_description',
                'gateway_transactions.tracking_code as bank_tracking_code',
                'requests.payment_status',
                'requests.payment_type',
                'requests.tracking_code'])
            ->selectRaw('requests.total_price - (requests.discount_percent * requests.total_price / ?)   as price_with_discount', [100])
            ->selectRaw('CONCAT(users.first_name, " ", users.last_name) as user_name')
            ->selectRaw('CONCAT(coaches.first_name, " ", coaches.last_name) as coach_name')
            ->where('requests.total_price','!=',0)
            ->where(function ($q) {
                $q->where('requests.payment_status', 'SUCCEED')
                    ->orWhere('requests.payment_type', 'OTHER');
            });

        $results = datatables($query)
                ->addColumn('total_price', function ($request) use ($config) {
                    if($request->payment_type == 'ONLINE') {
                        if ($request->bank_tracking_code == '') {
                            return (0);
                        } else {
                            return ($request->total_price);
                        }
                    }
                    else {
                        return ($request->total_price);
                    }
                })
            ->addColumn('price_with_discount', function ($request) use ($config) {
                if($request->payment_type == 'ONLINE') {
                    if ($request->bank_tracking_code == '') {
                        return (0);
                    } else {
                        return ($request->price_with_discount);
                    }
                }
                else{
                    return ($request->price_with_discount);

                }
            })
                ->addColumn('operation', function ($request) use ($config) {
                return view('admin.ads.operation', [
                    'config' => $config,
                    'object_id' => $request->id,
                ]);
                })
            ->addColumn('bank_tracking_code', function ($request) use ($config) {
                if($request->payment_type == 'ONLINE')
                {
                    if($request->bank_tracking_code == ''){
                        return('User error');
                    }
                    else{
                        return($request->bank_tracking_code);
                    }
                }
                elseif($request->payment_type == 'CREDIT')
                {
                    return('');
                }
                else
                {
                    return(substr($request->manual_description,strpos($request->manual_description, ":")+1));
                }
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('coach_name', function ($query, $keyword) {
                $sql = 'CONCAT(coaches.first_name, " ", coaches.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })

            ->filterColumn('introduction_method', function ($query, $keyword) {
                $sql = 'users.introduction_method like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('created_at', function ($query, $keyword) {
                $keyword=explode(',',$keyword);

                $startDate=explode('-',$keyword[0]);
                $startDate=implode('-',Verta::getGregorian($startDate[0],$startDate[1],$startDate[2]));

                $endtDate=explode('-',$keyword[1]);
                $endtDate=implode('-',Verta::getGregorian($endtDate[0],$endtDate[1],$endtDate[2]));

                $query->whereRaw('requests.created_at >= ?' ,["{$startDate}"]);
                $query->whereRaw('requests.created_at <= ?' ,["{$endtDate}"]);
            })
            ->rawColumns(['operation'])
            ->make(true);

        return $results;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

}
