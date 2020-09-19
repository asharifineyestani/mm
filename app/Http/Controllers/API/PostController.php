<?php

namespace App\Http\Controllers\API;

use App\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Facades\ResultData as Result;

class PostController extends Controller
{

    public $pathMedia = '/uploads/posts/';


    public function index()
    {
        $p['page'] = Input::get('page');
        $p['per'] = Input::get('per');
        $p['offset'] = ($p['page'] - 1) * $p['per'];

        $posts = Post::published()
            ->select('*')
            ->orderBy('id', 'Desc')
            ->limit(1);


        if (Input::get('q'))
            $posts = $posts->where('title', 'like', '%' . Input::get('q') . '%');

        if ($p['per'] && $p['per'])
            $posts = $posts->offset($p['offset'])
                ->limit($p['per']);


        $data['posts'] = $posts->get();
        return Result::setData($data)->get();
    }

    public function show($id)
    {
        $data['post'] = Post::published()
            ->select('*')
            ->where('id' , $id)
            ->orderBy('id', 'Desc')
            ->first();

        return Result::setData($data)->get();
    }
}
