<?php

namespace App\Http\Controllers\EditorArea;

use App\Helpers\Sh4Helper;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CreditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
	protected function rules()
	{
		return [
			'user_id' => 'required|integer|exists:users,id',
			'type' => 'required|string',
			'amount' => 'required|integer'
		];
	}

    public function index()
    {
        return view('editorArea.credits.index');
    }

    public function table()
    {

        $config = new \stdClass();
        $config->routeName = 'EditorArea.payments';
        $config->table = 'payments';
        $config->buttons = ['edit' => false, 'destroy' => true];

        $recors = CreditLog::leftJoin('credits', 'credit_logs.credit_id', '=', 'credits.id')
	        ->where(function ($query){
		        $query->Where('type', 'like', 'DEPOSIT' . '%')
			        ->orWhere('type', 'like', 'WITHDRAW' . '%');
	        })
            ->Where('credit_logs.status', '>', 0)
            ->leftJoin('users', 'users.id', '=', 'credits.user_id')
            ->select(\DB::raw(
                'balance, accepted,'.
                'CONCAT(users.first_name, " ", users.last_name) as user_name,' .
                'users.email as email, credit_logs.tracking_code as credit_tracking_code ,credit_logs.amount as price,credit_logs.id as id,credit_logs.details,credit_logs.created_at as created_at,credit_logs.type as type'
            ));

        $results = datatables($recors)
            ->addColumn('operation', function ($request) use ($config) {
                return view('editorArea.ads.operation', [
                    'config' => $config,
                    'object_id' => $request->id,
                ]);
            })
            ->editColumn('type', function ($q) {
                return __('mm.payment.type.' . $q->type);
            })
            ->editColumn('accepted', function ($q) {
                return __('mm.accepted.' . $q->accepted);
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('credit_tracking_code', function ($query, $keyword) {
                $sql = 'credit_logs.tracking_code like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
	        ->editColumn('created_at', function ($record){
	        	return Sh4Helper::formatPersianDate($record->created_at);
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

        $users = User::select('id', 'first_name', 'last_name')->get();


        return view('editorArea.credits.create', ['users' => $users]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->rules());

        $fields = $request->only(['user_id', 'amount', 'type']);

        $user = User::find($fields['user_id']);


        $allowedTypes = [
            'deposit' => 'DEPOSIT_MANUALLY_ADMIN',
            'withdraw' => 'WITHDRAW_MANUALLY_ADMIN',
        ];


        if ($fields['type'] == 'withdraw') {

            $user->withdraw($fields['amount'], $allowedTypes[$fields['type']], $user->id, ['description' => 'WITHDRAW_MANUALLY_ADMIN']);
        } elseif ($fields['type'] == 'deposit') {

            $user->deposit($fields['amount'], $allowedTypes[$fields['type']], $user->id, ['description' => 'DEPOSIT_MANUALLY_ADMIN']);
        }

        return redirect('admin/editor-area/credits')->with(['message' => 'تراکنش با موفقیت انجام شد']);
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

	public function getUsers(Request $request)
	{
		$users = User::selectRaw(
				'id,' .
				'email as text'
			)
			->where('email', 'like', '%' . $request->search . '%')
			->orderBy('text', 'asc')
			->limit(20)
			->get();

		return response()->json($users);
    }
}
