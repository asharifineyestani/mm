<?php

namespace App\Http\Controllers;

use App\Helpers\BodyAnalyzer;
use App\Helpers\Sh4Helper;
use App\Http\Controllers\Controller;
use App\Mail\SendAdminMail;
use App\Mail\SendCoachMail;
use App\Models\Ads;
use App\Models\Addable;
use App\Models\CreditLog;
use App\Models\DiscountCode;
use App\User;
use App\Models\Role;
use App\Models\Body;
use App\Models\City;
use App\Models\Coach;
use App\Models\Country;
use App\Models\Image;
use App\Models\Plan;
use App\Models\PlanItem;
use App\Models\Price;
use App\Models\Province;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use PHPRtfLite;
use PHPRtfLite_Font;
use PHPRtfLite_List_Enumeration;
use PHPRtfLite_List_Numbering;
use DB;
use com\grandt\php\LipsumGenerator;
use PHPZip\Zip\Core\ZipUtils;
use \PHPZip\Zip\File\Zip as ZipArchiveFile;
use PHPZip\Zip\File\Zip as ZipArchive;
use App\Mail\SendUserMail;
use function Sodium\add;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use App\Models\Setting;
use App\Models\GatewayTransaction;
use SoapClient;
use App\Http\Controllers\TestController;



class RequestController extends Controller
{
    private $_errors = '';
    public $prefix = 'wb-body';
    public $admin = '';
    public $user = '';
    public $coach = '';
    public $coaches = '';
    public $originalPath = '';
    public $token = '';
    public $question=array();


    public function create()  {
        if (isset(Auth::user()->id)) {
            $this->user = User::where('id', Auth::user()->id)->first();
            $balance = User::find($this->user->id)->balance;
            $this->coaches = $this->getCoaches();
            $this->coaches->each(function ($item, $key) {
                if ($item['accept_new_student'] == -1) {
                    $request = $this->getLatestUserCoachRequest($this->user->id, $item['id']);
                    if (count($request) <= 0) {
                        $coch_array[] = $item['id'];
                        $this->coaches = User::select('users.id', 'users.email', 'users.birth_day', 'users.first_name', 'users.last_name', 'users.avatar', 'coach_fields.*')->roleIS('coach')->with(['plans' => function ($query) {
                            $query->orderBy('order');
                        }])
                            ->whereNotIn('users.id', $coch_array)
                            ->where('coach_fields.visible', '>', 0)
                            ->get();
                    } else {
                        $this->coaches = $this->getCoaches();
                    }
                } else {
                    $this->coaches = $this->getCoaches();

                }

            });
            $last_request = $this->getLatestUserRequest($this->user->id);
        }
        else {
            $last_request='';
            $balance = 0;
            $this->coaches = $this->getCoaches();
        }
        // $json = file_get_contents('http://www.tgju.org/?act=sanarateservice&client=tgju&noview&type=json');
        //$dollar = json_decode($json)->sana_sell_usd->price;
        $dollar = Setting::where('key', 'usd')
            ->limit(1)
            ->first();
        $dollar = $dollar->value;
        $questions = Question::get();
        $countries = Country::orderBy('id', 'asc')->get();
        $cities = City::orderBy('id', 'asc')->get();
        $coaches = $this->coaches;
        $score=$this->userScoring()['status'];
        $settings=Setting::where('type','json')->get();
        return view("user.pages.index", compact("questions", "coaches", "countries", 'dollar', 'cities', 'balance','score','settings','last_request'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function store(Request $request)
    {

        ////======get id user who is login
        $this->user = User::where('id', Auth::user()->id)->first();

        ////======get information about last admin in site for send email
        $this->admin = $this->whoIsAdmin();

        ////======get information about coach which select by user in request
        $this->coach = User::where('id', $request->post('coach-selected'))->first();

        ////======create new object for append plans which selected by user
        $planItems = new PlanItem();
        $planItems->setCoachId($this->coach->id);
        $planItems->setPlanIds($request->post('plans'));
        $discountCode = !empty($request->post('discount_code'))?$request->post('discount_code'): NULL;
        $discount = new DiscountCode();
        $plans = array('items' => []);
        foreach ($request->input('plans') as $item) {
            $plan = Plan::find($item);
            $price = Price::where('plan_id', $plan->id)->where('user_id', $this->coach->id)->first();
            array_push($plans['items'], [
                'plan_id' => $plan->id,
                'price' => $price->price,
                'title' => $plan->title,
                'type' => $plan->type,
            ]);
        }

////======create array from request
        $userRequest = array(
            'user_id' => $this->user->id,
            'coach_id' =>$this->coach->id,
            'total_price' => PriceController::total($planItems),
            'discount_percent' => $discount->getPercentDiscountFromCode($discountCode,$this->coach->id)->percent,
            'payment_status' => 'INIT',
            'payment_type' => $request->post('payment_type'),
            'program_status' => 0,
            'description' => !empty($request->post('description')) ? __('mm.payment.type.MANUALLY') . ' : ' . $request->get('description') : '',
            'questions' => json_encode(array_values($request->post('questions'))),
            'plans' => $plans,
            'tracking_code' => 1
        );

        ////======insert above array into request table
        $newRequest = new \App\Models\Request($userRequest);
        $newRequest->save();

        ////======create array from user body that post to this function
        $newBody = $request->post('body');
        $newBody['user_id'] = Auth::user()->id;
        $body = new Body($newBody);
        $body->save();


        ////======check folder for send image and rtf file in to it ,if folder not exsist create it
        $this->originalPath = public_path() . '/uploads/request/' . $newRequest->tracking_code;
        if (!file_exists($this->originalPath)) {
            mkdir($this->originalPath);
        }
        if (Input::hasFile('media_path')) {
            $files = $request->file('media_path');
            $this->uploadPicture($files, $newRequest->id);
        }
        $payment_type=$request->post('payment_type');

        $this->createRtfWord($newRequest, $body);
        $this->createArchiveFile();
        $this->deleteDirectory($this->originalPath);
        $tracking_code = $newRequest->tracking_code;
        $this->question=$request->post('questions');
        if($payment_type == 'OTHER' || $payment_type == 'CREDIT' ) {
              $this->smsConfiguration($tracking_code);
            $this->sendEmail($tracking_code);
        }
        return response()->json(['status' => true, 'data' => ['payment_type' => $payment_type, 'request_id' => $newRequest->id, 'discount_code' => $discountCode, 'tracking_code' => $tracking_code]]);
    }

    public function createRtfWord($request, $body)
    {
        $rtf = new PHPRtfLite();
        PHPRtfLite::registerAutoloader();
        $sect = $rtf->addSection();
        $content = "[Info]" . "\n";
        $content .= "Name=" . $this->user->first_name . "\n";
        $content .= "Family=" . $this->user->last_name . "\n";
        $content .= "Acquaintance =" . $this->user->introduction_method . "\n";
        $content .= "Birth=" . substr(Verta($this->user->birth_day), 2, 2) . "\n";
        $content .= "Sex=" .__('mm.rtf.'.$this->user->gender). "\n";
        $content .= "Blood=" . $this->user->blood_group . "\n";
        $content .= "Mobile=" . $this->user->mobile . "\n";
        $content .= "Email=" . $this->user->email . "\n";
        $content .= "Coach_Fname=" . $this->coach->first_name . "\n";
        $content .= "Coach_Lname=" . $this->coach->last_name . "\n";
        $content .= "Date=" . substr(Verta(date("Y/m/d")), 2, 8) . "\n";
        $content .= "[Sizes]" . "\n";
        $content .= "Height=" . $body->height . "\n";
        $content .= "Weight=" . $body->weight . "\n";
        $content .= "Neck=" . $body->neck . "\n";
        $content .= "Chest=" . $body->chest . "\n";
        $content .= "Biceps=" . $body->arm_in_contraction	 . "\n";
        $content .= "Forearm=" . $body->forearm . "\n";
        $content .= "Wrist=" . $body->wrist . "\n";
        $content .= "Waist=" . $body->waist . "\n";
        $content .= "Hip=" . $body->hip . "\n";
        $content .= "Thighs=" . $body->thigh . "\n";
        $content .= "Calves=" . $body->shin . "\n";
        $content .= "Ankle=" . $body->ankle . "\n";
        $content .= "[Questions]" . "\n";
        if ($request->questions!== null) {
            $counter = 0;
            $questions=json_decode($request->questions);
            foreach ($questions as $q) {
                if ($q->excerpt == NULL) {
                    $counter = $counter + 1;
                    $content .= "q" . $counter . "=" .  Sh4Helper::convertCharsToPersian($q->answer) . "\n";

                } else if ($q->excerpt != NULL) {
                    $content .= $q->excerpt . "=" . Sh4Helper::convertCharsToPersian($q->answer)  . "\n";
                }
            }

        }
        $sect->writeText($content);
        $rtf->save('uploads/request/' . $request->tracking_code . '/info.rtf');
    }
    public function createArchiveFile()
    {
        $zipper = new \Chumper\Zipper\Zipper;
        $files = scandir($this->originalPath);
        $folderZip= $zipper->make($this->originalPath . '.zip');
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $folderZip->add($this->originalPath . '/' . $file);
            }

        }
    }
    public function tracking(Request $request)
    {
        $erro_message='';
        $sliders = Ads::all();
        if ($request->track == 1) {
            if (filter_var($request->track_phar, FILTER_VALIDATE_EMAIL)) {
                $user = DB::table('users')->where('email', $request->track_phar)->get()->first();
                if ($user) {
                    $order = DB::table('requests')->where('user_id', $user->id)->orderBy('id', 'desc')->limit(1)->get()->first();

                }
            } else
                if (is_numeric($request->track_phar)) {
                    $order = DB::table('requests')->where('tracking_code', $request->track_phar)->orderBy('id', 'desc')->limit(1)->get()->first();

                }
            if (isset($order)) {
                if ($order->program_status > 0) {
                    $erro_message =' برنامه شما  در تاریخ ';
                    $erro_message.='<b style="display: inline-block;direction: ltr">'.Verta($order->updated_at).'</b>';
                    $erro_message.=' ارسال شده است. ';
                    return view('user.pages.client_tracking',compact('erro_message'));
                }
                elseif ($order->program_status < 0) {
                    $message = 'پرونده شما دارای مشکل است ';
                    $message_body = 'پیام مربی: ' . $order->coach_description;
                    $color = '#f43e3e';

                    return view('user.pages.tracking-message', compact('message', 'message_body', 'color', 'sliders'));
                }
                else {
                    $orders = DB::table('requests')->where([
                        // ['requests.id', '<=', $order->id],
                        ['requests.coach_id', '=', $order->coach_id],
                        ['requests.program_status', '=', 0]
                    ])->join('users', 'requests.user_id', '=', 'users.id')
                        ->select('users.first_name', 'users.last_name', 'users.email', 'requests.*')
                        ->orderBy('requests.created_at', 'asc')
                        ->where(function ($q) {
                            $q->where('requests.payment_status', 'SUCCEED')
                                ->orWhere('requests.payment_type', 'OTHER');
                        })
                        ->get();
                    $coach = DB::table('coach_fields')->where('user_id', $order->coach_id)->get()->first();
                    $coachName= DB::table('users')->where('id', $order->coach_id)->get()->first();

                    if (strlen($coach->emergency_message) > 5) {
                        $message_body = $coach->emergency_message;
                        $message = 'مربی برای شما پیامی دارد';
                        $color = '#0d8cd0';
                        return view('user.pages.tracking-message', compact('message', 'message_body', 'color', 'sliders'));
                    }
                    $count = DB::table('requests')->where([
                        ['id', '<=', $order->id],
                        ['coach_id', '=', $order->coach_id],
                        ['program_status', '=', 0]
                    ])
                        ->where(function ($q) {
                            $q->where('requests.payment_status', 'SUCCEED')
                                ->orWhere('requests.payment_type', 'OTHER');
                        })->count();
                    if ($coach->program_per_day != 0) {
                        $days_before_get = ceil($count / $coach->program_per_day);

                    } elseif ($coach->program_per_day == 0) {
                        $days_before_get = NULL;

                    }
                    return view('user.pages.tracking-table', compact('order', 'orders', 'days_before_get', 'sliders','coachName'));
                }
            }
            else {

                $erro_message = 'این کد در سیستم موجود نمی باشد';
                return view('user.pages.client_tracking',compact('erro_message'));
            }
        }
        else {

            return view('user.pages.client_tracking');
        }
    }
    public function getCoaches()
    {
        $availbaleGender=array('FEMALE','MALE');
        $setting = Setting::where('key','except')
            ->first();
        if(!empty($setting)) {
            $setting->value = json_decode($setting->value);
            if (isset(Auth::user()->id)) {
                $userGender = Auth::user()->gender;
                if ($setting->value->FEMALE == 1) {
                    if ($userGender == 'FEMALE') {
                        $availbaleGender = array('FEMALE');
                    }
                }
                if ($setting->value->MALE == 1) {
                    if ($userGender == 'MALE') {
                        $availbaleGender = array('MALE');
                    }
                }
            }
        }

        $result = User::select('users.id', 'users.email', 'users.birth_day', 'users.first_name', 'users.last_name', 'users.avatar', 'users.status','users.gender', 'coach_fields.*')
            ->roleIS('coach')->with(['plans' => function ($query) {
                $query->orderBy('order');
            }])
            ->where('coach_fields.visible', '>', 0)
            ->whereIn('users.gender', $availbaleGender)
            ->orderBy('coach_fields.order','asc')
            ->get();
        return $result;

    }



    public function storeAddables($id, $type, $path)
    {
        $data = array(
            'addable_id' => $id,
            'addable_type' => $type,
            'media_path' => $path,
            'category' => 'PICTURE'
        );
        $Addable = new Addable($data);
        $Addable->save();
        return true;
    }
    public function whoIsAdmin()
    {
        $result = User::select('users.id', 'users.email', 'users.birth_day', 'users.first_name', 'users.last_name', 'users.avatar', 'users.status')
            ->roleIS('admin')->first();
        return $result;
    }
    public function getLatestUserCoachRequest($usreId,$coachId)
    {
        $result = \App\Models\Request::where('user_id', $usreId)
            ->where('coach_id',$coachId)
            ->get();
        return $result;
    }
    public function getLatestUserRequest($userId){
        $result = \App\Models\Request::where('user_id', $userId)
            ->with('coach')
            ->orderBy('id','desc')
            ->first();
        return $result;
    }
    public function sendEmail($code)
    {
        $result= \App\Models\Request::with(['coach' ,'user'])
            ->where('tracking_code',$code)
            ->first();
        $body=Body::where('user_id',$result->user['id'])
            ->orderBy('id','desc')
            ->first();
        $paymentTransaction='';
        if($result->payment_type == 'ONLINE' || $result->payment_type == 'CREDIT' ) {
            $paymentTransaction=GatewayTransaction::where('request_id',$result->id)
                ->get()
                ->first();
        }
        $payment=array(
            'type' => $result->payment_type,
        );
        /* fill data for send email to Admin && User && Coach for this request */
        $data = array(
            'tracking_code' => $code,
            'plans' => $result->plans['items'],
            'coach_name' => $result->coach['first_name'].' '. $result->coach['last_name'],
            'user_info' => $result->user,
            'user_reagent' =>!empty($result->user['reagent']->email) ? $result->user['reagent']->email : NULL,
            'user_body' => $body,
            'questions' => $result->questions,
            'payment' => $payment,
            'paymentTransaction' =>!empty($paymentTransaction->tracking_code) ? $paymentTransaction->tracking_code : NULL,
            'description' =>$result->description
        );

      /*  Mail::to('fatemeh.aeeni20@gmail.com')->send(new SendUserMail($data['tracking_code']));
        Mail::to('fatemeh.aeeni20@gmail.com')
            ->send(new SendCoachMail($data));*/

/*     Mail::to($result->coach['email'])
               ->cc('A.babazadeh@gmail.com')
               ->send(new SendCoachMail($data));

        Mail::to($result->user['email'])->send(new SendUserMail($data['tracking_code']));
      //  Mail::to($result->coach['email'])->send(new SendCoachMail($data));
        Mail::to('admin@nikan.ir')->send(new SendCoachMail($data));*/

    }
    public function uploadPicture($files, $bodyId)
    {
        $type = Body::class;
        foreach ($files as $key => $value) {
            if ($key != 'EXTRA') {
                $picture = $files[$key];
                $result = $this->storeMedia($picture, 'picture');
                $picture->move($this->originalPath, $result);
                $this->storeAddables($bodyId, $type, $result);

            } else if ($key === 'EXTRA') {
                foreach ($files['EXTRA'] as $key => $value) {
                    $result = $this->storeMedia($value, 'picture');
                    $value->move($this->originalPath, $result);
                    $this->storeAddables($bodyId, $type, $result);
                }
            }

        }
    }
    public function showTrackingCode($code)
    {
        $tracking_code = $code;
        $settings=Setting::where('type','json')->get();
        return view('user.pages.end-request', compact('tracking_code','settings'));

    }
    public function userScoring(){
        if (isset(Auth::user()->id)){
            $result=\App\Models\Request::where('user_id',Auth::user()->id)
                ->where(function ($query) {
                    //  $query->where('payment_status', 'SUCCEED')
                    $query->whereIn('program_status',[1,2]);
                })
                ->orderBy('id', 'desc')
                ->first();
            if($result != null)
            {
                if($result->score == 0) {
                    return array('status' => true);
                }
                else {
                    return array('status' => false);
                }
            }
            else{
                return array('status' => false);
            }

        }
        else
        {
            return array('status' => false);
        }
    }
    public function smsConfiguration($code){
        $result= \App\Models\Request::with(['coach' ,'user'])
            ->where('tracking_code',$code)
            ->first();
        if($result->coach['sms'] == 1){
            $this->sendCoachMessage($result->coach,$code);
        }
        if($result->user['sms'] == 1){
            $this->sendUserMessage($result->user,$code);
        }
    }
    public function sendUserMessage($User,$code)
    {
        $client = new SoapClient("http://188.0.240.110/class/sms/wsdlservice/server.php?wsdl");

        $user = "sms690";

        $pass = "091214354670";

        $fromNum = "500010707";

        $toNum = array($User->mobile);

        $pattern_code = "jo3z3ywmyb";

        $input_data = array(
            "tracking-code" => $code
        );

        $client->sendPatternSms($fromNum, $toNum, $user, $pass, $pattern_code, $input_data);

        return true;
    }
    public function sendCoachMessage($coach,$code)
    {
        $client = new SoapClient("http://188.0.240.110/class/sms/wsdlservice/server.php?wsdl");

        $user = "sms690";

        $pass = "091214354670";

        $fromNum = "500010707";

        $toNum = array($coach->mobile);

        $pattern_code = "md84mht4r6";

        $input_data = array(
            "coach-name" => $coach['first_name']."  ".$coach['last_name'],
            "tracking-code" => $code
        );

        $client->sendPatternSms($fromNum, $toNum, $user, $pass, $pattern_code, $input_data);

        return true;
    }
    public function loginForm()
    {
        if(isset(Auth::user()->id)){
            return redirect('/admin');
        }
        else{
            return view('user.pages.login-panel-admin');
        }

    }
    public function checkUserCredit(Request $request)
    {
        $balance = User::find(Auth::user()->id)->balance;
        return $balance;
    }
    public function index()
    {
        $score=$this->userScoring()['status'];
        $settings=Setting::where('type','json')->get();

        if(Auth::id())
        {
            return redirect()->route('create-request');
        }
        else
        {
            return view('user.pages.start_index',compact('settings','score'));

        }
    }
    public function deleteDirectory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir")
                        rrmdir($dir."/".$object);
                    else unlink   ($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
    public function changeScore(Request $request)
    {
        $rate=$request->post('rate');
        $request=\App\Models\Request::where('user_id',Auth::user()->id)
            ->where('score','==',0)
            ->orderBy('id', 'desc')
            ->first();
        \App\Models\Request::where('user_id', Auth::user()->id)
            ->where('id', $request->id)
            ->update(['score' => $rate]);
        return true;
    }

}
