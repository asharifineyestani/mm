<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\Program;
use App\Models\Programs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Log;

class ProgramController extends Controller
{
    //
    public function store(Request $request)
    {

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
            $data['data'] = Program::insert($record);

        }

        return response()
            ->json($data)
            ->withCallback($request->input('callback'));


    }


    public function indexEmail()
    {
        return Program::select('id', 'created_at', 'data')->orderBy('id', 'Desc')->get();
    }


    public function showEmail($id)
    {
        return 999;
        return Program::select('id', 'created_at', 'data','email_id','checksum as key')->where('id', $id)->orderBy('id', 'Desc')->get();
    }

    public function indexAdapt()
    {
        return Email::select('*')->orderBy('id', 'Desc')->get();
    }


    public function showAdapt($id)
    {
        return Email::select('workout')->where('log_id', $id)->first();
    }


}
