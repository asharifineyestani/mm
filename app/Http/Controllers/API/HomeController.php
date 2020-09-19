<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Request;
use App\Facades\ResultData as Result;
use Auth;

class HomeController extends Controller
{
    public function index()
    {
        $data['post'] = Post::published()
            ->select('id', 'media_path', 'title')
            ->orderBy('id', 'Desc')
            ->first();

        $data['request'] = Request::select('id', 'created_at', 'coach_id','program_status' , 'coach_description','tracking_code')
//            ->where('program_status', 0)
            ->with(['coach' => function ($q) {
                return $q->select('first_name', 'last_name','id');

            }])
            ->where('user_id', Auth::user()->id)
            ->orderBy('id', 'Desc')
            ->first();


//        return Auth::user()->id;
        return Result::setData($data)->get();
    }
}
