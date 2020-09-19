<?php

namespace App\Http\Controllers\EditorArea;

use App\Models\DiscountCode;
use App\Rules\Timestamp;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use App\Helpers\Sh4Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DiscountController extends Controller
{
    private function rules()
    {
        $rules = [
            'coach_id' => 'required|numeric',
            'code' => 'required|string',
            'percent' => 'required|integer|between:1,100',
            'expired_at' => 'required',
        ];

        return $rules;
    }

    public function index()
    {
        //
        return view('editorArea.discounts.index');
    }


    public function getDiscounts(Request $request)
    {
        $query = DiscountCode::leftJoin('users', 'discount_codes.coach_id', '=', 'users.id')
            ->select(DB::raw(
                'discount_codes.id as id,' .
                'discount_codes.code as code,' .
                'discount_codes.percent as percent,' .
                'discount_codes.expired_at as expired_at,' .
                'CONCAT(users.first_name, " ", users.last_name) as coach'
            ));

        $results = datatables($query)
            ->addColumn('operation', function ($discount) {
                return view('editorArea.discounts.partials.operation', [
                    'discount_id' => $discount->id
                ]);
            })
            ->editColumn('expired_at', function ($discount) {
                return $discount->formatExpiredAt();
            })
            ->filterColumn('coach', function ($query, $keyword) {
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('percent', function ($query, $keyword) {
                $query->where('discount_codes.percent', $keyword);
            })
            ->rawColumns(['operation'])
            ->make(true);

        return $results;
    }

    public function create()
    {
        //
        $coaches = User::roleIs('coach')->get();
        return view('editorArea.discounts.create')->with(['coaches' => $coaches]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        if (!is_null(DiscountCode::where('coach_id', $request->coach_id)->where('code', $request->code)->first())){
            Session::flash('alert-info', 'error,' . __('mm.DiscountCode.Duplicate'));
            return redirect()->back();
        }
        else if (!is_null(DiscountCode::where('code', $request->code)->first())){
            Session::flash('alert-info', 'error,' . __('mm.DiscountCode.another_coach'));
            return redirect()->back();
        }

        $fields = $request->only(['coach_id', 'code', 'percent', 'expired_at']);
        $fields['expired_at'] = date('Y-m-d H:i:s', substr($request->get('expired_at'), 0, -3));
        DiscountCode::create($fields);
        return redirect('admin/editor-area/discounts')->with('message', 'کد تخفیف انتخابی با موفقیت ثبت گردید.');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $discount = DiscountCode::findOrFail($id);

        $coaches = User::roleIs('coach')->get();

        return view('editorArea.discounts.edit', [
            'coaches' => $coaches,
            'discount' => $discount
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->rules());
        if (!is_null(DiscountCode::where('coach_id', $request->coach_id)
                                    ->where('code', $request->code)
                                    ->where('id','!=' ,$id)
                                    ->first())){
            Session::flash('alert-info', 'error,' . __('mm.DiscountCode.Duplicate'));
            return redirect()->back();
        }
        else if (!is_null(DiscountCode::where('code', $request->code)
                                        ->where('id','!=' ,$id)
                                        ->first())){
            Session::flash('alert-info', 'error,' . __('mm.DiscountCode.another_coach'));
            return redirect()->back();
        }

        $fields = $request->only(['coach_id', 'code', 'percent', 'expired_at']);

        $fields['expired_at'] = date('Y-m-d H:i:s', substr($request->get('expired_at'), 0, -3));

        $discount = DiscountCode::findOrFail($id);

        $update = $discount->update($fields);

        if ($update) {
            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.discount_code')]));
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        if (DiscountCode::destroy($id)) {
            die(true);
        }
        die(false);
    }

    public function checkCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:discount_codes,code',
            'coach_id' => 'required|exists:users,id'
        ]);
        if ($validator->fails())
        {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }
        $code = $request->post('code');
        $coachId = $request->post('coach_id');
        $discount = new DiscountCode();
        $data = $discount->getPercentDiscountFromCode($code, $coachId);
        if($data == NULL){
            return response()->json(['status' => false, 'data' => NULL, 'message' => __("mm.DiscountCode.notـFound")]);
        }
        else if($data != NULL) {
            return response()->json(['status' => true, 'data' => $data,'message' => __("mm.DiscountCode.applied")]);
        }
        //return Carbon::now();
    }

}
