<?php

namespace App\Http\Controllers\API;

use App\Models\Study\Workout;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Facades\ResultData as Result;

class WorkoutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //

        $select = ['id','name'];

        $records = Workout::select($select)->limit(15);



        $p['page'] = Input::get('page');
        $p['per'] = Input::get('per');
        $p['offset'] = ($p['page'] - 1) * $p['per'];


        if (Input::get('q'))
            $records = $records->where('name', 'like', '%' . Input::get('q') . '%');


        if ($p['per'] && $p['per'])
            $records = $records->offset($p['offset'])
                ->limit($p['per']);


        return Result::setData($records->get())->get();


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

        $select = ['name', 'des as description','cat_type as type' ,
            'mechanism' ,
            'direction',
            'main_equipment',
            'more_equipment',
            'prepration',
            'execution',
            'target',
            'sinergist',
            'stabilizers',
            ];

        $records = Workout::select($select)->where('id', $id)->first();




        return Result::setData($records)->get();
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
