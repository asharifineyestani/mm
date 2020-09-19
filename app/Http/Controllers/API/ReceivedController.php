<?php

namespace App\Http\Controllers\API;

use App\Models\Program;
use App\Models\Received;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class ReceivedController extends Controller
{
    //


    public function store(Request $request)
    {
        return 9;


        Log::emergency($request->all()); #todo test

        $data['status'] = false;
        $data['message'] = null;
        $data['errors'] = null;

        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
        ]);


        $record = $request->only('data','checksum','email_id');

        $record['created_at'] = Carbon::now();
        $record['updated_at'] = Carbon::now();


        if ($validator->fails()) {

            $data['errors'] = $validator->errors();
        } else {


//            if (!@unserialize($record['data']))
//                $data['errors'] = ['wrong serialize' => 'data should be an serialized data.'];
//
//            else {
//                $data['status'] = true;
//                $data['message'] = 'Thanks Masoud. Information was received successfully.';
//                $data['data'] = Program::create($record);
//            }


            $data['status'] = true;
            $data['message'] = 'Thanks Masoud. Information was received successfully.';
            $data['data'] = Received::insert($record);

        }

        return response()
            ->json($data)
            ->withCallback($request->input('callback'));


    }


    public function index()
    {
        return Program::select('id', 'created_at', 'data')->orderBy('id', 'Desc')->get();
    }


    public function show($id)
    {
        return 999;
        return Program::select('id', 'created_at', 'data','email_id','checksum as key')->where('id', $id)->orderBy('id', 'Desc')->get();
    }
}
