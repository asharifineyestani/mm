<?php

namespace App\Sh4;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UserRequest;
use App\Mail\RegisterCode;
use App\Models\Role;
use App\Models\SmsLog as SMSLog;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Ipecompany\Smsirlaravel\Smsirlaravel;
use App\Facades\ResultData as Result;
use Validator;
use Illuminate\Support\Facades\Hash;


trait sh4Auth
{


    private $successStatus = 200;

    private $limitLog = [
        'deviceId' => 4000,
        'mobile' => 4000,
        'ip' => 4000,
    ];

    private $limitMinute = 1;


//    private function getMobile(Request $request)
//    {
//
//
//        $mobile = $request->input('mobile');
//        $device_id = $request->input('device_id');
//        $ip = \Request::ip();
//
//
//        $validator = Validator::make($request->all(), [
//            'mobile' => 'required|regex:/(09)[0-9]{9}/',
//        ]);
//
//        $limitSmsSend = Carbon::now()->subMinutes($this->limitMinute);
//
//        $countSmsInLimitationTime = SMSLog::where('mobile', $mobile)
//            ->where('created_at', '>=', $limitSmsSend)
//            ->count();
//
//        if ($validator->fails()) {
//            $result = Result::setErrors($validator->errors());
//
//        } elseif ($countSmsInLimitationTime) {
//
//            $result = Result::setErrors(['sms-limitation' => 'کد فعال سازی برای شما ارسال شده است']);
//
//        } else {
//
//            $error = $this->setErrorInResultIfLimitSms($mobile, $device_id);
//            if ($error) {
//
//                $result = Result::setErrors($error);
//            } else {
//
//                SMSLog::setLog($mobile, $ip, $device_id);
//                $code = SMSLog::where('mobile', $mobile)->orderBy('id', 'Desc')->value('code');
//                //$option = new OptionController();
//                //$option->sendSmsCode($code, $mobile);
//
//
//                $this->sendSMSCode($code, $mobile);
//
//                $result = Result::setData([
//                    'mobile' => $mobile,
////                    'code' => $code #todo
//                ]);
//            }
//        }
//
//
//        return $result->get();
//
//    }
//
//
//    private function getCode(Request $request)
//    {
//
//        $result = null;
//        $code = $request->only('code');
//        $mobile = $request->only('mobile');
//        $log = SMSLog::validFromLog($code, $mobile);
//
//        $validator = Validator::make($request->all(), [
//            'mobile' => 'required',
//            'code' => 'required',
//            'device_id' => 'required',
//        ]);
//
//
//        if ($validator->fails()) {
//            $result = Result::setErrors([$validator->errors()]);
//            $user = false;
//        } elseif (!$log) {
//            $result = Result::setErrors(['wrong_code' => ['wrong code']]);
//        } else {
//            $user = User::select('*')
//                ->where('mobile', $request->only('mobile'))
//                ->first();
//        }
//
//        if (isset($user) && !$validator->fails() && $log) {
//            $result = $this->setResultAfterLogin($user);
//        } else if (!$validator->fails() && $log) {
//            $data = [
//                'user_registered' => false,
//                'mobile' => $request->only('mobile')['mobile'],
//            ];
//
//            $result = Result::setData($data);
//        }
//        return $result->get();
//    }
//
//    private function getCodeEmail(Request $request)
//    {
//
//        $result = null;
//        $code = $request->only('code');
//        $email = $request->only('email');
//        $log = SMSLog::validFromLog($code, $email);
//
//        $validator = Validator::make($request->all(), [
//            'email' => 'required | email',
//            'code' => 'required',
//            'device_id' => 'required',
//        ]);
//
//
//        if ($validator->fails()) {
//            $result = Result::setErrors([$validator->errors()]);
//            $user = false;
//        } elseif (!$log) {
//            $result = Result::setErrors(['wrong_code' => ['wrong code']]);
//        } else {
//            $user = User::select('*')
//                ->where('email', $request->only('email'))
//                ->first();
//        }
//
//
//        if (isset($user) && !$validator->fails() && $log) {
//            $result = $this->setResultAfterLogin($user);
//
//        } else if (!$validator->fails() && $log) {
//            $data = [
//                'user_registered' => false,
//                'email' => $request->only('email')['email'],
//            ];
//
//            $result = Result::setData($data);
//        }
//        return $result->get();
//    }

    #step [1]
    public function getEmail(Request $request)
    {
        $email = $request->input('email');
        $device_id = $request->input('device_id');
        $ip = \Request::ip();


        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'device_id' => 'required',
        ]);

        $limitSmsSend = Carbon::now()->subMinutes($this->limitMinute);

        $countSmsInLimitationTime = SMSLog::where('mobile', $email)
            ->where('created_at', '>=', $limitSmsSend)
            ->count();

        if ($validator->fails()) {
            $result = Result::setErrors([$validator->errors()]);

        } elseif ($countSmsInLimitationTime) {

            $result = Result::setErrors(['email_limitation' => 'کد فعال سازی برای شما ایمیل شده است']);

        } else {

            $error = $this->setErrorInResultIfLimitSms($email, $device_id);
            if ($error) {
                $result = Result::setErrors($error);
            } else {
                SMSLog::setLog($email, $ip, $device_id);
                $code = SMSLog::where('mobile', $email)->orderBy('id', 'Desc')->value('code');
                //$option = new OptionController();
                //$option->sendSmsCode($code, $email);


                Mail::to($email)->send(new RegisterCode($code));


                $result = Result::setData([
                    'email' => $email,
                    'code' => $code #todo
                ]);
            }
        }


        return $result->get();

    }

    #step [2]
    public function activeWithCode(Request $request)
    {

        $code = $request->get('code');
        $mobile = $request->get('mobile');
        $email = $request->get('email');
        $unique = $email ?? $mobile;
        $user = new User();
        if ($mobile)
            $user = $user->where('mobile', $mobile);
        elseif ($email)
            $user = $user->where('email', $email);


        $log = SMSLog::validFromLog($code, $unique);


        if ($log) {
            $user->update(['status' => 1]);
            $result = $this->setResultAfterLogin($user->first());

        } else {
            $error['wrong_code'] = ["wrong_code"=> ["کد اشتباه وارد شده است"]];
            $result = Result::setErrors($error);
        }

        return $result->get();
    }

    #step [3]
    public function registerEmail(UserRequest $request)
    {

        Log::emergency($request->all());
//       return $request->all();

        $fields = $request->only(['first_name', 'last_name', 'email', 'password', 'mobile', 'blood_group', 'birth_day', 'device_id', 'gender']);
        $ip = \Request::ip();


        $controller = new Controller();
//            $fields['password'] = bcrypt($request->get('password'));
        $fields['password'] = Hash::make($request->get('password'));
        $fields['status'] = 0;
        if ($request->hasFile('avatar')) {
            $fields['avatar'] = $controller->storeMedia($request->file('avatar'), 'picture');
        }

//        return $fields;
        $user = User::create($fields);
//        $role = Role::where('name', 'user')->first();
//        $user->roles()->attach($role);


        SMSLog::setLog($fields['email'], $ip, $fields['device_id']);
        $code = SMSLog::where('mobile', $fields['email'])->orderBy('id', 'Desc')->value('code');
        Mail::to($fields['email'])->send(new RegisterCode($code));


        $result = Result::setData([
            'email' => $user->email,
            'message' => "It's requires code to be verified",
            'code' => $code,  #Todo sh4: this is not secure
        ]);


        return $result->get();

    }


//    private function registerWithCode(Request $request)
//    {
//
//        $code = $request->only('code');
//        $mobile = $request->only('mobile');
//        $email = $request->only('email');
//        $unique = $email ?? $mobile;
//        $log = SMSLog::validFromLog($code, $unique);
//        if ($log) {
////            $incomplete_user = User::where('mobile', $mobile)->first();
////
////            if ($incomplete_user)
////                User::find($incomplete_user->id)->delete();
//
//            return $this->register($request);
//        } else {
//            $error['wrong_code'] = ["wrong code"];
//            $result = Result::setErrors($error);
//        }
//
//        return $result->get();
//    }
//
//
    public function loginWithPassword(Request $request)
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            switch ($user->status) {
                case 0:
//                    $result = $this->setResultAfterLogin($user);
                    $result = Result::setErrors(['inactive' => ["کاربر کد خود را تایید نکرده است"]]);
                    break;
                case 1:
                    $result = $this->setResultAfterLogin($user);
                    break;
                default:
                    $result = Result::setErrors(['disable' => ["کاربر توسط ادمین غیر فعال شده است"]]);
            }


        } else {
            $result = Result::setErrors(['unauthorised' => ["اطلاعات وارد شده صحیح نمی باشد"]]);
        }
        return $result->get();


//
//        $credentials = request(['mobile', 'password']);
//
//        if (!Auth::attempt($credentials))
//            return response()->json([
//                'message' => 'Unauthorized'
//            ], 401);
//        $user = $request->user();
//        $tokenResult = $user->createToken('Personal Access Token');
//        $token = $tokenResult->token;
//        if ($request->remember_me)
//            $token->expires_at = Carbon::now()->addWeeks(1);
//        $token->save();
//        return response()->json([
//            'access_token' => $tokenResult->accessToken,
//            'token_type' => 'Bearer',
//            'expires_at' => Carbon::parse(
//                $tokenResult->token->expires_at
//            )->toDateTimeString()
//        ]);


    }


    private function register(Request $request)
    {


        Log::emergency($request->all()); #todo test

        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'password' => 'required',
            'name' => 'required',
        ]);

        $role = $request->get('role');


        switch ($role) {
            case "user":
                $input['role_id'] = 3;
                break;
            case "coach":
                $input['role_id'] = 2;
                $input['status'] = 0;
                break;

            default:
                return Result::setErrors(['wrong-role' => ["رول اشتباه وارد شده است"]])->get();
        }

        if ($validator->fails()) {
            $result = Result::setErrors([$validator->errors()]);
        } else {
            $input['mobile'] = $request->get('mobile');
//            $input['password'] = bcrypt($request->get('password'));
//            $input['password'] =  hash($request->get('password')) ;
            $input['password'] = Hash::make($request->get('password'));


            $user = User::where('mobile', $input['mobile'])->first();

            if ($user)
                $user->update($input);
            else
                $user = User::create($input);


//            $result = $this->setResultAfterLogin($user);

            $result = Result::setData([
                'message' => "It's requires code to be verified",
            ]);
        }

        return $result->get();
    }


//    private function details()
//    {
//        $user = Auth::user();
//        $result = $this->setResultAfterLogin($user);
//        return $result->get();
//    }


    private function setErrorInResultIfLimitSms($mobile, $device_id)
    {
        $error = null;
        $countMobile = SMSLog::where('mobile', $mobile)->count();
        $countIP = SMSLog::where('ip', \Request::ip())->count();
        $countDeviceID = SMSLog::where('device_id', $device_id)->count();

        if ($countMobile > $this->limitLog['mobile']) {
            $error["countMobile"] = ["count-mobile"];
        }
        if ($countIP > $this->limitLog['ip']) {
            $error['countIP'] = ["count-ip"];

        }
        if ($countDeviceID > $this->limitLog['deviceId']) {
            $error['countDeviceId'] = ["countDeviceId"];
        }
        return $error;
    }


    private function setResultAfterLogin($user)
    {


        $user = User::where('id', $user->id)->first();

        $data['token'] = $user->createToken('mm')->accessToken;


        $data['user_registered'] = true;
        $data['status'] = $user->status;
        $data['mobile'] = $user->mobile;
//        $data['role'] = $user->role;
        $data['first_name'] = $user->first_name;
        $data['last_name'] = $user->last_name;
        $data['gender'] = $user->gender;
        $data['name'] = $user->name;
        $data['age'] = $user->age;
        $data['id'] = $user->id;
        return Result::setData($data);
    }


}
