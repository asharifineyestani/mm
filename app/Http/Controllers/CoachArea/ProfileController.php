<?php

namespace App\Http\Controllers\CoachArea;

use App\Models\City;
use App\Models\Coach;
use App\Models\Country;
use App\User;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;
use Auth;

class ProfileController extends Controller
{
    protected function validation(Request $request){
        $request->validate([
            'first_name' => 'required | string | max:50',
            'last_name' => 'required | string | max:50',
            'gender' => 'required | in:male,female',
            'mobile' => 'required | string',
            'program_per_day' => 'required | numeric',
            'bank_card' => 'required | numeric',
            'education' => 'required|string'
        ]);
    }

    public function edit()  {
	    $coach = User::leftJoin('coach_fields', 'users.id', '=', 'coach_fields.user_id')
		    ->where('users.id', Auth::user()->id)
		    ->firstOrFail();

	    $cities = City::all();
	    $countries = Country::all();
	    return view('coachArea.profile.edit', [
        	'coach' => $coach,
		    'cities' => $cities,
		    'countries' => $countries
        ]);
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
            $birth_day = date('Y-m-d H:i:s', substr($data['birth_day'], 0, -3));
            $data['birth_day'] = $birth_day;
        }else{
            unset($data['birth_day']);
        }
        /*encrypt password by laravel encryption*/
        if ($request->input('password')){
            $data['password'] = Hash::make($data['password']);
        }else{
            unset($data['password']);
        }

	    $user_data = [
		    'first_name' => $data['first_name'],
		    'last_name' => $data['last_name'],
		    'birth_day' => $data['birth_day'],
		    'mobile' => $data['mobile'],
		    'gender' => $data['gender'],
		    'blood_group' => $data['blood_group'],
		    'introduction_method' => $data['introduction_method'],
		    'country_id' =>   !empty($request["country_id"]) ? $request["country_id"] : NULL,
		    'city_id' =>  !empty($request["city_id"]) ? $request["city_id"] : NULL,


	    ];

	    $coach_fields_data = [
		    'national_code' => $data['national_code'],
		    'education' => $data['education'],
		    'bank_card' => $data['bank_card'],
		    'program_per_day' => $data['program_per_day'],
		    'address' => $data['address'],
		    'about_gym' => $data['about_gym'],
		    'background' => $data['background']
	    ];

	    $CoachFields = Coach::where('user_id', Auth::user()->id)->first();

	    if (!is_null($CoachFields)){
		    $CoachFields->update($coach_fields_data);
	    }else{
	    	$coach_fields_data['user_id'] = Auth::user()->id;
	    	$coach = new Coach($coach_fields_data);
	    	$coach->save();
	    }

        if (Auth::user()->update($user_data)){
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

            Session::flash('alert-info', 'success,'  . __('mm.popup.update.success', ['name'=>__('mm.user.profile')]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name'=>__('mm.coach.singular')]));
        return redirect()->back();
    }

	public function changePassword()
	{
		return view('coachArea.profile.change_password');
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
