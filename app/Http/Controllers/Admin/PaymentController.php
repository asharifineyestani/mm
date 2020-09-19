<?php
namespace App\Http\Controllers\Admin;
use App\Models\CreditLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.payments.index');
    }

    public function table(Request $request)
    {
        $config = new \stdClass();
        $config->routeName = 'admin.payments';
        $config->table = 'payments';
        $config->buttons = ['edit' => false, 'destroy' => true];
        $recors = CreditLog::leftJoin('credits', 'credit_logs.credit_id', '=', 'credits.id')
            ->Where('type', 'like', 'WITHDRAW_BUY' . '%')
            ->Where('credit_logs.status', '>', 0)
            ->leftJoin('requests', 'requests.id', '=', 'credit_logs.related_id')
            ->leftJoin('users', 'users.id', '=', 'requests.user_id')
            ->leftJoin('users as coaches', 'coaches.id', '=', 'requests.coach_id')
            ->where('requests.total_price','!=',0)
            ->select(\DB::raw(
                'CONCAT(users.first_name, " ", users.last_name) as user_name,' .
                'CONCAT(coaches.first_name, " ", coaches.last_name) as coach_name,' .
                'requests.payment_status,requests.coach_id,requests.user_id,requests.id as request_id,' .
                'requests.payment_type as request_payment_type,requests.tracking_code as request_tracking_code,' .
                'credit_logs.tracking_code as credit_tracking_code ,credit_logs.amount as price,credit_logs.id as id,credit_logs.details,credit_logs.created_at as created_at,credit_logs.type as type'
            ));
        $results = datatables($recors)
            ->addColumn('operation', function ($request) use ($config) {
                return view('admin.ads.operation', [
                    'config' => $config,
                    'object_id' => $request->id,
                ]);
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('coach_name', function ($query, $keyword) {
                $sql = 'CONCAT(coaches.first_name, " ", coaches.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('request_tracking_code', function ($query, $keyword) {
                $sql = 'requests.tracking_code like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
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
        Log::emergency($id);
        if (CreditLog::where('id', $id)->update(['status' => -1]))
            die(true);
        else
            die(false);
    }
    /*
     * show view for list of manually payment
     * author aeini
     * created at 1398/11/27
     *
     */
    public function manually(){
        return view('admin.payments.manually');
    }
    /*
    * get list of manually payment
    * author aeini
    * created at 1398/11/27
    *
    */
    public function tablemanually(Request $request)
    {
        $config = new \stdClass();
        $config->routeName = 'admin.payments';
        $config->table = 'payments';
        $config->buttons = ['edit' => false, 'destroy' => true];
        $recors = \App\Models\Request::where('requests.payment_status','INIT')
            ->where('requests.payment_type','OTHER')
            ->leftJoin('users', 'users.id', '=', 'requests.user_id')
            ->leftJoin('users as coaches', 'coaches.id', '=', 'requests.coach_id')
            ->select(\DB::raw(
                'CONCAT(users.first_name, " ", users.last_name) as user_name,' .
                'CONCAT(coaches.first_name, " ", coaches.last_name) as coach_name,' .
                'requests.id as id,requests.payment_status,requests.coach_id,requests.user_id,requests.id as request_id,' .
                'requests.payment_type as request_payment_type,requests.tracking_code as request_tracking_code,'.
                'requests.created_at,(requests.total_price-(requests.total_price*requests.discount_percent/100)) as price,'.
                'requests.description as manual_description'
            ))
            ->where('requests.total_price','!=',0);

        $results = datatables($recors)
            ->addColumn('manual_description', function ($request) use ($config) {
                return(substr($request->manual_description,strpos($request->manual_description, ":")+1));
            })
            ->addColumn('operation', function ($request) use ($config) {
                return view('admin.ads.operation_manually', [
                    'config' => $config,
                    'object_id' => $request->id,
                ]);
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('coach_name', function ($query, $keyword) {
                $sql = 'CONCAT(coaches.first_name, " ", coaches.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('request_tracking_code', function ($query, $keyword) {
                $sql = 'requests.tracking_code like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->rawColumns(['operation'])
            ->make(true);
        return $results;
    }
}
