<?php

namespace App\Http\Controllers\ClientArea;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Addable;

class RequestController extends Controller
{
    public function index()
    {
        $balance = User::find(Auth::user()->id)->balance;

        return view('clientArea.requests.index',compact('balance'));
    }

    public function getRequests(Request $request)
    {
        $query = \App\Models\Request::leftJoin('users as coaches', 'requests.coach_id', '=', 'coaches.id')
            ->where('user_id', Auth::user()->id)
            ->select(DB::raw(
                'requests.id as id,' .
                'CONCAT(coaches.first_name, " ", coaches.last_name) as coach_name,' .
                'requests.total_price as total_price,' .
                'requests.plans as plans,' .
                'requests.payment_status as payment_status,' .
                'requests.payment_type as payment_type,' .
                'requests.program_status as program_status,' .
                'requests.created_at as created_at,' .
                'requests.tracking_code as tracking_code'
            ))
            ->where(function ($q) {
                $q->where('requests.payment_status', 'SUCCEED')
                    ->orWhere('requests.payment_type', 'OTHER');
            });

        $results = datatables($query)
            ->addColumn('operation', function ($request){
                return view('clientArea.requests.partials._operation', [
                    'request_id' => $request->id
                ]);
            })
            ->editColumn('plans', function ($request){
                $plans = '';
                if (isset($request->plans['items'])){
                    foreach ($request->plans['items'] as $item){
                        $plans .= '<p class="small text-info">' . $item['title'] . ' ' . '&rlm;(' . $item['price'] . ')' . ' ' . __('mm.public.'.$item['type']) . '</p>';
                    }
                }else{
                    $plans = '-';
                }
                return $plans;
            })
            ->editColumn('payment_status', function ($request){
                return __('mm.payment.status')[$request->payment_status];
            })
            ->editColumn('payment_type', function ($request){
                return __('mm.request')[$request->payment_type];
            })
            ->editColumn('program_status', function ($request){
                return __('mm.programStatuses')[$request->program_status];
            })
            ->editColumn('created_at', function ($request){
                return '<span class="small">' . $request->formatDate() . '</span>';
            })
            ->rawColumns(['plans', 'created_at', 'operation'])
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
        $balance = User::find(Auth::user()->id)->balance;

        $request = \App\Models\Request::where('user_id',Auth::user()->id)->findOrFail($id);
        $typeQuestion=gettype($request->questions);
        $addable=Addable::where('addable_id',$id)
            ->get();

        return view('clientArea.requests.show', [
            'request' => $request,
            'addable' => $addable,
            'balance' => $balance,
            'typeQuestion'=>$typeQuestion

        ]);

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
