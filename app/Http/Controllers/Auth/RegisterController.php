<?php

namespace App\Http\Controllers\Auth;

use App\Models\City;
use App\Models\Country;
use App\Models\Setting;
use App\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Hekmatinasser\Verta\Verta;
use http\Env\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Intervention\Image\Facades\Image;
use Morilog\Jalali\Jalalian;
use App\Rules\Recaptcha;
use App\Http\Requests\UserRequest;
use GuzzleHttp\ClientInterface;
use SoapClient;
class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';
    public function showRegistrationForm()
    {
        return redirect()->route('create-request');
    }
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
   protected function validator(array $data)
    {
        return Validator::make($data, [
                "email"=>['required' ,'string', 'email', 'max:255', 'unique:users'],
                "password"=>['required', 'string', 'min:3', 'confirmed'],
                "first_name"=>['required', 'string', 'max:255'],
                "last_name"=>['required', 'string', 'max:255'],
                "mobile"=>['required' , "numeric","unique:users","regex:/^([0-9\s\-\+\(\)]*)$/","min:11"],
                "gender"=>['required'],
                "birth_day"=>['required'],
                "captcha" => ['required','captcha']
        ]);
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    public function register(\Illuminate\Http\Request $request)
    {
        $this->validator($request->all())->validate();
         $date = explode("/" , $request["birth_day"]);
        $date= Verta::getGregorian($date[0] ,$date[1] ,$date[2]);
        $date = Carbon::createFromFormat('Y-m-d H',$date[0]."-".$date[1]."-".$date[2]." 00");

        if ($request->hasFile("avatar")){
            $avatar = $this->storeMedia($request->file('avatar'),'picture');
        }else{
            $avatar = "";
        }
        if($request["reagent_id"]!=null){
            $reagent_id=$request["reagent_id"];
            $this->giveRewardtoReagent($reagent_id);
        }
        else{
            $reagent_id=null;
        }
        $user = User::create([
            "city_id"=>!empty($request["city_id"])? $request["city_id"] : NULL,
            "avatar"=>$avatar,
            "email"=>$request["email"],
            "password"=>Hash::make($request["password"]),
            "first_name"=>$request["first_name"],
            "last_name"=>$request["last_name"],
            "mobile"=>$request["mobile"],
            "gender"=>$request["gender"],
            "birth_day"=>$date,
            "blood_group"=>!empty($request["blood_group"])? $request["blood_group"]: 'NOT-SELECTED',
            "introduction_method"=>!empty($request["SelectedRdioReagent"])? $request["SelectedRdioReagent"] : NULL,
            "sms"=>1,
            "settings"=>"{[locale:fa]}",
            "status"=>"1",
            "local"=>['local'=> 'fa'],
            'reagent_id'=>$reagent_id,
            "country_id"=>!empty($request["country_id"]) ? $request["country_id"] : NULL
        ]);
        if ($user){
           // $user->roles()->sync(array_values([3]));
            Auth::login($user);
            //sms to user
          /*  $client = new SoapClient("http://188.0.240.110/class/sms/wsdlservice/server.php?wsdl");

            $user = "sms690";

            $pass = "091214354670";

            $fromNum = "500010707";

            $toNum = array($request["mobile"]);

            $pattern_code = "d6s90wmpdn";

            $input_data = array(
                "user-name" => $request["first_name"]."  ".$request["last_name"]
            );

            $client->sendPatternSms($fromNum, $toNum, $user, $pass, $pattern_code, $input_data);*/

            echo "SUCCESS";
        }else{
            echo "FAILED";
            die();
        }
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
    private function setLanguage($id, $locale = "fa")
    {
        $user = User::find($id);

        $settings = $user->settings;

        $settings['locale'] = $locale;

        $user->settings = json_encode($settings);

        $user->save();

        return true;
    }
    /*
    * give reward to reagent
    * author aeini
    * created_at 1398/11/15
    *
    */
    public function giveRewardtoReagent($reagent_id){
        $reward = Setting::where('key', 'reward')
            ->limit(1)
            ->first();
        $reward = $reward->value;
        $user = User::find($reagent_id);
        $user->deposit($reward, 'DEPOSIT_REWARD', $user->id, ['description' => 'Introducing the site to friends']);
        return true;
    }
}
