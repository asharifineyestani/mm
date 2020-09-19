<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Models\Country;
use App\User;
use Illuminate\Auth\SessionGuard;
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
                'mobile' => 'required',
        ]);
    }

    public function edit()
    {
        $user = Auth::user();
	    $cities = City::all();
	    $countries = Country::all();

        return view('admin.profile.edit', compact('user', 'cities', 'countries'));
    }

    public function update(Request $request)
    {
    	$user = Auth::user();

    	$this->validation($request);
        $request->validate([
            'mobile' => 'required | unique:users,mobile,' . $user->id,
        ]);

        $data = $request->all();

        if ($request->input('birth_day')){
            /*convert unix to timestamp for save in mysql*/
            $birth_day = date('Y-m-d', substr($data['birth_day'], 0, -3));
            $data['birth_day'] = $birth_day;
        }else{
            unset($data['birth_day']);
        }
	    unset($data['avatar']);
        if ($user->update($data)){
	        // save avatar
	        $old_image_path = $user->avatar;
	        if ($request->hasFile('avatar')){
		        $files = $request->file('avatar');
		        if (isset($files[0])){
			        $image_path = $this->storeMedia($files[0], 'picture');
			        $user->avatar = $image_path;
			        $user->update();
			        // delete old avatar
			        $this->unlinkMedia($old_image_path);
		        }
	        }


            Session::flash('alert-info', 'success,'.__('mm.popup.update.success',['name'=>__('mm.public.admin')]));
            return redirect()->back();
        }

        Session::flash('alert-info', 'error,'.__('mm.popup.update.error',['name'=>__('mm.public.admin')]));

        return redirect()->back();
    }

	public function changePassword()
	{
		return view('admin.profile.change_password');
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
