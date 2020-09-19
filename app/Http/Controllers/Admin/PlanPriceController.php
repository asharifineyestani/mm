<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\Price;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class PlanPriceController extends Controller
{

	protected function rules($id = '')
	{
		return [
			'price' => 'required|integer',
			'user_id' => "required|exists:users,id",
			'plan_id' => "required|exists:plans,id"
		];
	}

    public function index()
    {
        return view('admin.plans-specified.index');
    }

	public function getPlansSpecified(Request $request)
	{
		$query = Price::leftJoin('users as coaches', 'prices.user_id', '=', 'coaches.id')
			->leftJoin('plans', 'prices.plan_id', '=', 'plans.id')
			->selectRaw(
				'CONCAT(coaches.first_name, " ", coaches.last_name) as coach,' .
				'plans.title as plan_title,' .
				'prices.price as price,' .
				'plans.type as plan_type,' .
				'prices.user_id as user_id,' . 
				'prices.plan_id as plan_id'
			);

		$results = datatables($query)
			->editColumn('plan_type', function ($planPrice){
				return __('mm.public.' . $planPrice->plan_type);
			})
			->addColumn('operation', function ($planPrice){
				return view('admin.plans-specified.partials._operation', [
					'user_id' => $planPrice->user_id,
					'plan_id' => $planPrice->plan_id
				]);
			})
			->filterColumn('coach', function ($query, $keyword){
			    $sql = 'CONCAT(coaches.first_name, " ", coaches.last_name) like ?';
			    $query->whereRaw($sql, '%' . $keyword . '%');
			})
			->filterColumn('price', function ($query, $keyword){
			    $query->where('prices.price', '=', $keyword);
			})
			->rawColumns(['operation'])
			->make(true);

		return $results;
    }

    public function create()
    {
    	$coaches = User::roleIs('coach')->get();
    	$plans = Plan::all();

        return view('admin.plans-specified.create', [
        	'coaches' => $coaches,
	        'plans' => $plans
        ]);
    }

    public function store(Request $request)
    {
	   $request->validate($this->rules());

	   if (!is_null(Price::where('user_id', $request->user_id)->where('plan_id', $request->plan_id)->first())){
		   Session::flash('alert-info', 'error,' . __('mm.price_already_exists'));
		   return redirect()->back();
	   }

	   $price = Price::create($request->all());

	   if ($price){
		   Session::flash('alert-info', 'success,'.__('mm.popup.add.success',['name'=>__('mm.plan_specified.singular')]));
	   }

	   return redirect()->route('admin.plansSpecified.index');;
    }

    public function edit($id)
    {
    	$ids = array_map('intval', explode('-', $id));
    	$price = Price::where('user_id', $ids[0])
		    ->where('plan_id', $ids[1])
		    ->firstOrFail();

	    $coaches = User::roleIs('coach')->get();
	    $plans = Plan::all();

        return view('admin.plans-specified.edit', [
        	'price' => $price,
	        'coaches' => $coaches,
	        'plans' => $plans
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([

        	'price' => 'required|integer'

        ]);

	    $ids = array_map('intval', explode('-', $id));

	    $record=Price::where('user_id', $request->user_id)

                            ->where('plan_id', $request->plan_id)

                            ->first();
        if (!is_null($record))
        {
            if($record->price == (int)$request->price)
            {
                 Session::flash('alert-info', 'error,' . __('mm.price_already_exists'));
                //return redirect()->back();
                 return redirect('admin/plans-specified');
            }
            else
            {
                Price::where('user_id', $ids[0])

                        ->where('plan_id', $ids[1])

                        ->update(['price' => (int)$request->price]);

                Session::flash('alert-info', 'success,'.__('mm.popup.update.success',['name'=>__('mm.plan_specified.singular')]));

                return redirect('admin/plans-specified');
            }
        }
	    $update = Price::where('user_id', $ids[0])
		    ->where('plan_id', $ids[1])
	        ->update(['user_id' => $request->user_id,'plan_id'=> $request->plan_id,'price' => (int)$request->price]);

	    if ($update){
		    Session::flash('alert-info', 'success,'.__('mm.popup.update.success',['name'=>__('mm.plan_specified.singular')]));
	    }
        return redirect('admin/plans-specified');

    }

    public function destroy($id)
    {
	    $ids = array_map('intval', explode('-', $id));
	    if (Price::where('user_id', $ids[0])->where('plan_id', $ids[1])->delete()){
	    	die(true);
	    }

	    die(false);
    }
}
