<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\BodyRequest;
use App\Mail\SendBodyChangeToCoaches;
use App\Models\Body;
use App\Sh4\RequestHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Facades\ResultData as Result;

class BodyController extends Controller
{
    //

    use RequestHelper;


    public function update(BodyRequest $request)
    {
        $id = Auth::user()->id;

//        $id = 25102;

        $body = Body::where('user_id', $id)->orderBy('created_at', 'desc');



        $body->update($request->all());


        $request = \App\Models\Request::where('user_id', $id)->orderBy('created_at', 'desc')->with('coach')->first();



        $dataEmail['user'] = Auth::user();
        $dataEmail['body'] = $body->first();



        $requestId =  \App\Models\Request::where('user_id',$id)->latest()->first()->id;

        $this->emailHandler($requestId, 'CHANGE_BODY');

        return Result::setData($body->first())->get();

    }


    public function show($id = 'current')
    {
        if ($id == 'current')
            $id = Auth::user()->id;
//        $id = 25102;

        $data['body'] = Body::where('user_id', $id)->orderBy('created_at', 'desc')->first();



        return Result::setData($data)->get();

    }
}
