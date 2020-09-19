<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use App\Models\Price;
use App\Models\Question;
use App\Models\Role;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Intervention\Image\Facades\Image;


class UserController extends Controller
{

    public function checkEmail(Request $request)
    {
        $email = $request->get('email');
       if (User::where('email', $email)->count()){
            $data['status'] = true;
            $data['user_registered'] = true;
            return response()->json($data);
        }

        else {
            $data['status'] = true;
            $data['user_registered'] = false;
            return response()->json($data);
        }

    }

    public function update(UserRequest $request)
    {

     //   Auth::loginUsingId(1); #todo test

        $fields = ['first_name', 'last_name', 'mobile', 'gender', 'city_id', 'birth_date', 'blood_group', 'introduction_method', 'bio','reagent_id'];

        $id = Auth::user()->id;

        $request->validate([
//            'first_name' => 'required', #todo test
//            'last_name' => 'required',
        ]);

        $input = $request->only($fields);


        if (Input::hasFile('avatar'))
            $input['avatar'] = $this->storeAvatar($request->file('avatar'));


        if (empty($input['avatar']))
            $input['avatar'] = User::where('id', $id)->first()->avatar;

        User::where('id', $id)->update($input);

        return back();
    }


    private function storeAvatar($originalImage)
    {
        $name = time() . $originalImage->getClientOriginalName();
        $thumbnailImage = Image::make($originalImage);
        $thumbnailPath = public_path() . $this->pathThumbnail;
        $originalPath = public_path() . $this->pathAvatar;
        $thumbnailImage->save($originalPath . $name);
        $thumbnailImage->resize(150, 150);
        $thumbnailImage->save($thumbnailPath . $name);

        return $name;
    }

    public function store(UserRequest $request)
    {

        $fields = ['first_name', 'last_name', 'email', 'mobile', 'gender', 'city_id', 'birth_date', 'blood_group', 'introduction_method'];

        $request->validate([
//            'first_name' => 'required', #todo test
//            'last_name' => 'required',
        ]);

        $input = $request->only($fields);

        $input['password'] = bcrypt($request->get('password'));


        if (Input::hasFile('avatar'))
            $input['avatar'] = $this->storeAvatar($request->file('avatar'));


        $userId = User::insertGetId($input);

        $this->setLanguage($userId);

        Auth::loginUsingId($userId);

        $data['status'] = true;
        $data['data']['user'] = Auth::user();
        return response()->json($data);

    }
    private function setLanguage($id, $locale = "fa")
    {
        $user = User::find($id);

        $settings = $user->settings;

        $settings['locale'] = $locale;

        $user->settings = json_encode($settings);

        $user->save();

        return true;
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function refreshCaptcha()
    {
        return captcha_img();
    }

}
