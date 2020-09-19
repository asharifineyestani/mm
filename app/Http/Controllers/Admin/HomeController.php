<?php

namespace App\Http\Controllers\Admin;

use App\Models\Coach;
use App\Models\GatewayTransaction;
use App\Models\GatewayTransactionsLog;
use App\Models\Plan;
use App\User;
use App\Models\Request;
use App\Models\Study\Workout;
use App\Models\Study\Supplement;
use App\Models\Study\Nutrient;
use App\Models\Study\Gym;
use App\Models\Study\Equipment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        if (Auth::user()->isRole('coach')){
            return redirect()->route('CoachArea.requests.index');
        }
        else if (Auth::user()->isRole('admin')){
            $users = User::all()->count();
            $requests = Request::all()->count();
            $coaches = User::roleIS('coach')->get()->count();
            $transactions = GatewayTransaction::all()->count();
            $successTransactions = GatewayTransactionsLog::where('result_code', 1)->count();
            $activeCoaches = User::roleIS('coach')->whereNotIn('status',[0,-1,-2])->get()->count();
            $inactiveCoaches = $coaches - $activeCoaches;
            $plans = Plan::all()->count();
            $workouts=Workout::all()->count();
            $supplements=Supplement::all()->count();
            $nutrients=Nutrient::all()->count();
            $gyms=Gym::all()->count();
            $equipments=Equipment::all()->count();


            return view('admin.index', compact('users', 'requests', 'coaches', 'transactions',
                'inactiveCoaches', 'activeCoaches', 'plans', 'successTransactions',
                'workouts','supplements','nutrients','gyms','equipments'));
        }
        else if (Auth::user()->isRole('editor')){
            return redirect()->route('EditorArea.requests.index');
        }

        else
            return redirect()->route('ClientArea.requests.index');
    }

}
