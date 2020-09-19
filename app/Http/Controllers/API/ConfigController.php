<?php

namespace App\Http\Controllers\API;

use App\Models\Question;
use App\User;
use App\Http\Controllers\Controller;
use App\Facades\ResultData as Result;

class ConfigController extends Controller
{
    //
    public function index()
    {
        $records['coaches'] = User::select('id','first_name','last_name','avatar')->roleIs('coach')->with('plans')->get();

        $records['questions'] = Question::with('answers')->get();

        return Result::setData($records)->get();

    }


}
