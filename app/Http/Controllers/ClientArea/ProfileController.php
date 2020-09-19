<?php

namespace App\Http\Controllers\ClientArea;

use App\Models\City;
use App\Models\Country;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;

class ProfileController extends Controller
{
    protected function validation(Request $request){
        $request->validate([
            'first_name' => 'required | string | max:50',
            'last_name' => 'required | string | max:50',
            'gender' => 'required',
            'mobile' => 'required | string',
        ]);
    }

    public function edit()
    {
        $user = Auth::user();
        $Url="";
        if(!empty($user->avatar)){
            if(file_exists(public_path().$user->avatar)){
                $Url=$user->avatar;
            }
            else{
                $Url=url("/")."/images/avatars/default.png";
            }
        }
        else
        {
            $Url=url("/")."/images/avatars/default.png";

        }
        $cities = City::all();
        $countries = Country::all();
        $balance = User::find(Auth::user()->id)->balance;

        return view('clientArea.profile.edit',compact('user', 'cities', 'countries','balance','Url'));
    }

    public function update(Request $request)
    {
        $this->validation($request);
        $request->validate([
            'mobile' => 'required | string | unique:users,mobile,' . Auth::user()->id,
        ]);
        $data = $request->all();

        if ($request->input('birth_day')){
            /*convert unix to timestamp for save in mysql*/
            $birth_day = date('Y-m-d H:i:s',substr($data['birth_day'],0,-3));
            $data['birth_day'] = $birth_day;
        }else{
            unset($data['birth_day']);
        }

        /*encrypt password by laravel encryption*/
        if ($request->input('password')){
            $data['password'] = bcrypt($data['password']);
        }else{
            unset($data['password']);
        }
        $data['country_id'] = !empty($data['country_id']) ? $data['country_id'] : NULL;
        $data['city_id'] = !empty($data['city_id'])? $data['city_id'] : NULL;
        unset($data['avatar']);
        if (Auth::user()->update($data)){
            // save avatar
            $old_image_path = Auth::user()->avatar;
            if ($request->hasFile('avatar')){
                $files = $request->file('avatar');
                if (isset($files[0])){
                    $image_path = $this->storeMedia($files[0], 'picture');
                    Auth::user()->avatar = $image_path;
                    Auth::user()->update();
                    // delete old avatar
                    $this->unlinkMedia($old_image_path);
                }
            }

            Session::flash('alert-info', 'success,'.__('mm.popup.update.success',['name'=>__('mm.user.singular')]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,'.__('mm.popup.update.error',['name'=>__('mm.user.singular')]));
        return redirect()->back();
    }

    public function changePassword()
    {
        $balance = User::find(Auth::user()->id)->balance;

        return view('clientArea.profile.change_password',compact('balance'));
    }

    public function updatePassword(Request $request)
    {
        if(!Hash::check($request->previous_password, Auth::user()->password)){
            return redirect()->back()->withErrors([
                'previous_password' => 'رمز عبور فعلی صحیح وارد نشده است'
            ]);
        }else{
            $request->validate([
                'password' => 'required|string|min:6|confirmed|different:previous_password'
            ]);

            $update = Auth::user()->update(['password' => Hash::make($request->password)]);

            if ($update){
                Session::flash('alert-info', 'success,' . 'رمز عبور با موفقیت تغییر یافت.');
            }

            return redirect()->back();
        }
    }
}
