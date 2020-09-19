<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Study\Category;
use App\Models\Study\Nutrient;
use App\Models\Study\Supplement;
use App\Models\Study\Gym;
use App\Models\Study\Workout;
use App\Models\Study\Equipment;
use App\Models\Ads;
use App\Models\Addable;

class QrController extends Controller
{
    public $Qr = array();

    public function __construct()
    {
        $this->Qr = array(
            'last_supplement' => $this->getLatestSupplements(),
            'last_nutrient' => $this->getLatestNutrient(),
            'last_workout' => $this->getLatestWorkout(),
            'last_equipment' => $this->getLatestEquipment()
        );
    }

    public function index()
    {
        $nutrients = Nutrient::all();

        $supplements = Supplement::all();

        $workouts = Workout::all();

        $equipments = Equipment::all();

        $sliders = Ads::all();

        return view('user.qr.index', compact('nutrients', 'supplements', 'workouts', 'equipments', 'sliders'));
    }

    public function show_equ($id)
    {
        $data = Equipment:: find($id);

        $workouts = Workout::all();

        $Qr = $this->Qr;

        return view('user.qr.pages.equipment', compact('data', 'Qr', 'workouts'));
    }

    public function show_supp($id)
    {
        $data = Supplement:: find($id);

        $Qr = $this->Qr;

        return view('user.qr.pages.supplement', compact('data', 'Qr'));

    }

    public function show_nut($id)
    {
        $data = Nutrient:: find($id);

        $Qr = $this->Qr;

        return view('user.qr.pages.nutrient', compact('data', 'Qr'));

    }

    public function show_work($id)
    {
        $data = Workout:: find($id);

        $Qr = $this->Qr;

        return view('user.qr.pages.workout', compact('data', 'Qr'));
    }

    public function getLatestNutrient()
    {
        return Nutrient::inRandomOrder()->first();
    }

    public function getLatestEquipment()
    {
        return Equipment::inRandomOrder()->first();

    }

    public function getLatestSupplements()
    {
        return Supplement::inRandomOrder()->first();

    }

    public function getLatestWorkout()
    {
        return Workout::inRandomOrder()->first();
    }
    public function  searchCategory(Request $request){
        $stringResult='<ul class="ul-result">';
       $nutrients = Nutrient::where('name', 'LIKE', '%' . $request->str . '%')
                       ->orwhere('en_name', 'LIKE', '%' . $request->str . '%')
                       ->get();
        $supplements = Supplement::where('name', 'LIKE', '%' . $request->str . '%')
            ->orwhere('en_name', 'LIKE', '%' . $request->str . '%')
            ->get();

        $workouts = Workout::where('name', 'LIKE', '%' . $request->str . '%')
            ->orwhere('en_name', 'LIKE', '%' . $request->str . '%')
            ->get();

        $equipments = Equipment::where('name', 'LIKE', '%' . $request->str . '%')
            ->orwhere('en_name', 'LIKE', '%' . $request->str . '%')
            ->get();
        if(count($nutrients)>0){
            $stringResult.='<h3>'.__("study.nutrient.plural").'</h3>';
            foreach ($nutrients as $nutrient)
            {
                if(count($nutrient->addables)>0){
                    $media_nutrient = $nutrient->addables->first()->media_path;
                }
                else{
                    $media_nutrient=url('/')."/user/assets/img/default-avatar.jpg";
                }
                $stringResult.='<li><a href="'.url('/').'/nutrient/'.$nutrient->id.'">
                                    <div class="col-md-3 nopad">
                                    <img width="50" height="50" src="'.$media_nutrient.'">
                                    </div>
                                    <div class="col-md-9 nopad">'.$nutrient->name.'<br></div>
                                    </a></li>';
            }
        }
        else if(count($supplements)>0){
            $stringResult.='<h3>'.__("study.supplement.plural").'</h3>';
            foreach ($supplements as $supplement)
            {
                if(count($supplement->addables)>0){
                    $media_supplement = $supplement->addables->first()->media_path;
                }
                else{
                    $media_supplement=url('/')."/user/assets/img/default-avatar.jpg";
                }
                $stringResult.='<li><a href="'.url('/').'/supplement/'.$supplement->id.'">
                                    <div class="col-md-3 nopad">
                                    <img width="50" height="50" src="'.$media_supplement.'">
                                    </div>
                                    <div class="col-md-9 nopad">'.$supplement->name.'<br></div>
                                    </a></li>';
            }
        }
        else if(count($workouts)>0){
            $stringResult.='<h3>'.__("study.workout.plural").'</h3>';
            foreach ($workouts as $workout)
            {
                if(count($workout->addables)>0){
                    $media_workout = $workout->addables->first()->media_path;
                }
                else{
                    $media_workout=url('/')."/user/assets/img/default-avatar.jpg";
                }
                $stringResult.='<li><a href="'.url('/').'/workout/'.$workout->id.'">
                                    <div class="col-md-3 nopad">
                                    <img width="50" height="50" src="'.$media_workout.'">
                                    </div>
                                    <div class="col-md-9 nopad">'.$workout->name.'<br></div>
                                    </a></li>';
            }
        }
        else if(count($equipments)>0){
            $stringResult.='<h3>'.__("study.equipment.plural").'</h3>';
            foreach ($equipments as $equipment)
            {
                if(count($equipment->addables)>0){
                    $media_equipment = $equipment->addables->first()->media_path;
                }
                else{
                    $media_equipment=url('/')."/user/assets/img/default-avatar.jpg";
                }
                $stringResult.='<li><a href="'.url('/').'/equipment/'.$equipment->id.'">
                                    <div class="col-md-3 nopad">
                                    <img width="50" height="50" src="'.$media_equipment.'">
                                    </div>
                                    <div class="col-md-9 nopad">'.$equipment->name.'<br></div>
                                    </a></li>';
            }
        }
        else{
            $stringResult.='نتیجه ای یافت نشد';
        }
        $stringResult.='</ul>';
        return $stringResult;
        }

}
