<?php

namespace App\Http\Controllers\API;

use App\Models\Addable;
use App\Models\Study\Workout;
use Doctrine\DBAL\Schema\Schema;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    //

    public function index()
    {
        return Addable::where('addable_type','<>','App\Models\Study\Workout')->get();



    }

    public function workout()
    {
        $selectFields = [
            "id",
            "name",
            "first_cat",
            "second_cat",
            "type",
            "general_des",
            "morabiman_des",
            "cat_type",
            "mechanism",
            "direction",
            "main_equipment",
            "more_equipment",
            "des",
            "prepration",
            "execution",
            "target",
            "sinergist",
            "stabilizers",
            "lang",
            "en_name",
            "en_cat_type",
            "en_mechanism",
            "en_direction",
            "en_main_equipment",
            "en_more_equipment",
            "en_des",
            "en_prepration",
            "en_execution",
            "en_target",
            "en_sinergist",
            "en_stabilizers",
            "en_general_des",
            "en_morabiman_des"
        ];

        $lastId = Workout::orderBy('id', 'Desc')->first()->id;

        $workouts = \App\Models\Workout::select($selectFields)->where('id', '>', $lastId)->limit(60)->get()->toArray();


        foreach ($workouts as $row) {
            $row['category_id'] = 1;
            $count = Workout::where('id', $row['id'])->count();

            if ($count < 1) {
                $new = Workout::create($row);
                $this->addable(\App\Models\Workout::find($new->id));

            }


        }

    }

    public function addable($workout)
    {
        $fieldsToRefactor = [
            'pic1' => 'PICTURE',
            'pic2' => 'PICTURE',
            'pic3' => 'PICTURE',
            'pic4' => 'PICTURE',
            'vid1' => 'VIDEO',
            'vid2' => 'VIDEO',

        ];

        foreach ($fieldsToRefactor as $field => $type) {

            $where = [
                'addable_id' => $workout->id,
                'addable_type' => 'App\Models\Study\Workout',
                'media_path' => $workout->$field,
                'category' => $type,
            ];

            $row = Addable::where($where)->first();


            if ($workout->$field && !$row) {
                Addable::create($where);
            }
        }

    }


    public function gif()
    {
        $gifs = DB::table('gifs')->select(['addable_id', 'addable_type', 'media_path', 'category', 'id as created_at', 'updated_at'])->where('category', 'GIF')->get();


        foreach ($gifs as $gif) {

            $where = [
                'addable_id' => $gif->addable_id,
                'addable_type' => 'App\Models\Study\Workout',
                'media_path' => $gif->media_path,
                'category' => 'GIF',
            ];

            Addable::insert($where);
        }
        return $gifs;

    }
}
