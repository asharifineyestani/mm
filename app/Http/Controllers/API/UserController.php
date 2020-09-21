<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UserRequest;
use App\Models\Email;
use App\Sh4\sh4Auth;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Auth;
use Illuminate\Support\Facades\Input;
use App\Facades\ResultData as Result;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
    //
    use sh4Auth;


    /**
     * @param $value
     * #todo sh4 testing
     * hatman bayad hazf she
     */
    public function destroy($value)
    {
        User::where('email', $value)
            ->orWhere('id', $value)
            ->orWhere('mobile', $value)
            ->delete();  #todo sh4 testing
    }


    /**
     *
     * #todo sh4 testing
     * hatman bayad hazf she
     */
    public function index()
    {
        $data = User::limit(10)->orderBy('id', 'Desc')->get();


        return Result::setData($data)->get();
    }


    /**
     *
     * #todo sh4 testing
     * hatman bayad hazf she
     */
    public function logs()
    {
        return DB::table('sms_logs')->select('code', 'device_id', 'mobile as key')->limit(3)->orderBy('id', 'Desc')->get();
    }


    public function update(UserRequest $request, $id)
    {

        Log::emergency($request->all());
        $fields = $request->only([
            "city_id",
            "email",
            "first_name",
            "last_name",
            "mobile",
            "gender",
            "birth_day",
            "blood_group",
            "introduction_method",
            "description",
            "phone"
        ]);

        if ($request->get('avatar_path'))
            $fields['avatar'] = Input::get('avatar_path');

        //        $controller = new Controller();
        //        if ($request->hasFile('avatar')) {
        //            $fields['avatar'] = $controller->storeMedia($request->file('avatar'), 'picture');
        //        }

        User::where('id', $id)->update($fields);

        return Result::setData(User::find($id))->get();


    }


    public function updateProfile(ProfileRequest $request)
    {
        $id = Auth::user()->id;

        return $this->update($request, $id);
    }


    public function profile()
    {
        $id = Auth::user()->id;

        $data['user'] = User::where('id', $id)->with(['requests' => function ($q) {
            $q->select('id', 'coach_id', 'user_id')->orderBy('id', 'Desc')->with(['coach' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'avatar');
            }])->first();
        }])->first();

        return Result::setData($data)->get();
    }


    public function show($id)
    {
        $data['user'] = User::find($id);

        return Result::setData($data)->get();
    }


    public function workout($id = 'sample')
    {

        $id = $this->getUserId($id);

        $wp = User::select(['id', 'first_name', 'last_name'])
            ->where('id', $id)
            ->with(['workout' => function ($q) {
                return $q->orderBy('id', 'Desc')->where('workout', '<>', "\"\"")->where('workout', '<>', null);
            }])->first();
        return Result::setData($wp)->get();

    }


    public function nutrition($id = 'sample')
    {

        $id = $this->getUserId($id);
        $wp = User::select(['id', 'first_name', 'last_name'])->where('id', $id)
            ->with(['nutrition' => function ($q) {
                return $q->orderBy('id', 'Desc')->where('nutrition', '<>', "\"\"")->where('nutrition', '<>', null);
            }])->first();
        return Result::setData($wp)->get();

    }


    public function analyze($id = 'sample')
    {
        $id = $this->getUserId($id);

        $wp['user'] = User::select(['id', 'first_name', 'last_name'])->where('id', $id)->with(['analyze' => function ($q) {
            return $q->orderBy('id', 'Desc')->where('analyze', '<>', "\"\"")->where('analyze', '<>', null);
        }])->first();
        $wp['chart'] = $this->chart();


        return Result::setData($wp)->get();

    }


    public function sounds($id = 'sample')
    {
        $id = $this->getUserId($id);

        $wp['user'] = User::select(['id', 'first_name', 'last_name'])->where('id', $id)
            ->with(['sounds' => function ($q) {
                return $q->orderBy('id', 'Desc')->where('nutrition', '<>', "\"\"")->where('workout', '<>', null);
            }])->first();


        return Result::setData($wp)->get();

    }


    public function myRequests($id)
    {

        $id = Auth::user()->id;

        $wp = User::select(['id', 'first_name', 'last_name'])->where('id', $id)->with(['requests' => function ($q) {
            $q->where(function ($query) {
                $query->where('payment_status', 'SUCCEED')
                    ->orWhere('payment_type', 'OTHER');
            })->orderBy('id', 'desc');
        }])->first();

        return Result::setData($wp)->get();

    }


    public function chart()
    {
        $data = [];
        $i = $j = $k = 0;

        $analyses = Email::where('user_id', Auth::user()->id)->select('analyze', 'created_at')->get()->toArray();


        foreach ($analyses as $item) {


            if (isset($item['analyze']['BF']['Percent']) && $item['analyze']['BF']['Percent'] > 0) {
                $data['BF'][$i]['created_at'] = $item['created_at'];
                $data['BF'][$i]['Percent'] = $item['analyze']['BF']['Percent'];
                $i++;
            }

            if (isset($item['analyze']['WHR']['Ratio']) && $item['analyze']['WHR']['Ratio'] > 0) {
                $data['WHR'][$j]['created_at'] = $item['created_at'];
                $data['WHR'][$j]['Ratio'] = $item['analyze']['WHR']['Ratio'];
                $j++;
            }


            if (isset($item['analyze']['BMI']['Amount']) && $item['analyze']['BMI']['Amount'] > 0) {
                $data['BMI'][$k]['created_at'] = $item['created_at'];
                $data['BMI'][$k]['Amount'] = $item['analyze']['BMI']['Amount'];
                $k++;
            }

        }

        return $data;

    }


    private function getUserId($id)
    {

        $userId = null;

        switch ($id) {
            case "female":
                $email = 'sample-female@morabiman.com';
                $user = User::where('email', $email)->first();
                if ($user)
                    $userId = $user->id;
                break;
            case "male":
                $email = 'sample-male@morabiman.com';
                $user = User::where('email', $email)->first();
                if ($user)
                    $userId = $user->id;
                break;
            case "current":
                $userId = $id = Auth::user()->id;
                break;
            case "me":
                $userId = $id = Auth::user()->id;
                break;
            default:
                if (Auth::user()->roleIs('admin')->first())
                    $userId = $id;
        }

        return $userId;
    }
}
