<?php

namespace App\Http\Controllers\Admin;

use App\Models\Study\Gym;
use App\User;
use App\Models\Coach;
use App\Models\Plan;
use App\Models\Price;
use App\Http\Requests\PlanRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class PlanController extends Controller
{
    public function index()
    {
        return view('admin.plans.index');
    }

	public function getPlans()
	{
		$query = Plan::select('*');

		$results = datatables($query)
			->editColumn('type', function ($plan){
				return __('mm.public.' . $plan->type);
			})
			->addColumn('operation', function ($plan){
				return view('admin.plans.partials._operation', [
					'plan_id' => $plan->id
				]);
			})
			->rawColumns(['operation'])
			->make(true);

		return $results;
	}

    public function create()
    {
      $coaches = User::select('id', 'email', 'birth_day', 'first_name', 'avatar')->roleIS('coach')->with(['plans' => function ($query) {
            $query->orderBy('order');
        }])->get();
        return view('admin.plans.single',compact('coaches'));
    }

    public function store(PlanRequest $request)
    {
        $plan = Plan::create($request->all());

	    if ($plan){
		    Session::flash('alert-info', 'success,'.__('mm.popup.add.success',['name'=>__('mm.plan.singular')]));
	    }

	    return redirect()->route('admin.plans.index');
    }

    public function edit($id)
    {
        $coaches = User::select('id', 'email', 'birth_day', 'first_name', 'avatar')->roleIS('coach')->with(['plans' => function ($query) {
            $query->orderBy('order');
        }])->get();
        $plan = Plan::find($id);
        return view('admin.plans.single',compact('coaches','plan'));
    }

    public function update(PlanRequest $request, $id)
    {
        $plan = Plan::find($id)->update($request->all());
        if ($plan){
	        Session::flash('alert-info', 'success,'.__('mm.popup.update.success',['name'=>__('mm.plan.singular')]));
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
	    $plan = Plan::findOrFail($id);
	    if ($plan->delete()) {
		    die(true);
	    }
	    die(false);
    }
}
