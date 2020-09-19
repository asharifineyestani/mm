<?php

namespace App\Http\Controllers\CoachArea;

use App\Helpers\Bitel;
use App\Mail\RequestStatusChange;
use App\Models\Addable;
use App\Models\Body;
use App\Models\City;
use App\Models\Country;
use App\Models\Plan;
use App\Models\Price;
use App\Models\Role;
use App\User;
use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\Builder\DefaultUuidBuilder;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Question;

class RequestController extends Controller
{
    private function validation(Request $request)
    {
        $request->validate([
                'first_name' => 'required | string',
                'last_name' => 'required | string',
                'email' => 'required | string | email | unique:users',
            ]
        );
    }
    public function index()
    {
        return view('coachArea.requests.index');
    }

    public function getRequests(Request $request)
    {
        $query = \App\Models\Request::leftJoin('users', 'requests.user_id', '=', 'users.id')
            ->where('requests.coach_id', Auth::user()->id)
            ->where(function ($q) {
                $q->where('requests.payment_status', 'SUCCEED')
                    ->orWhere('requests.payment_type', 'OTHER');
            })
            ->select(DB::raw(
                'requests.id as id,' .
                'CONCAT(users.first_name, " ", users.last_name) as name,' .
                'users.email as user_email,' .
                'users.mobile as user_mobile,' .
                'requests.tracking_code as tracking_cod,' .
                'requests.created_at as request_date,' .
                'requests.program_status as p_status,' .
                'requests.tracking_code as tracking_code'
            ));

        $results = datatables($query)
            ->editColumn('request_date', function ($request) {
                return '<span class="small">' . $this->formatDate($request->request_date) . '</span>';
            })
            ->addColumn('operation', function ($request) {
                return view('coachArea.partials._operation', [
                    'request_id' => $request->id,
                    'tracking_code' => $request->tracking_code
                ]);
            })
            ->addColumn('program_status', function ($request) {
                return view('coachArea.partials._program_status', [
                    'p_status' => $request->p_status,
                    'request_id' => $request->id
                ]);
            })
            ->addColumn('checkbox', function ($request) {
                return view('coachArea.partials._input_check_box', [
                    'request_id' => $request->id
                ]);
            })
            ->filterColumn('name', function ($query, $keyword) {
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, '%' . $keyword . '%');
            })
            ->filterColumn('request_date', function ($query, $keyword) {
                $date = array_map('intval', explode('/', $this->arabicNumbers($keyword)));
                $date_g = verta()->setDate($date[0], $date[1], $date[2])->formatGregorian('Y-m-d');
                $query->whereDate('requests.created_at', $date_g);
            })
            ->filterColumn('program_status', function ($query, $keyword) {
                $query->where('requests.program_status', $keyword);
            })
            ->rawColumns(['operation', 'program_status', 'request_date', 'checkbox'])
            ->make(true);

        return $results;
    }

    public function create()
    {
        $questions = Question::get();
        $result = User::select('users.id', 'users.email', 'users.birth_day', 'users.first_name', 'users.last_name', 'users.avatar', 'users.status','users.gender', 'coach_fields.*')
            ->roleIS('coach')->with(['plans' => function ($query) {
                $query->orderBy('order');
            }])
            ->where('coach_fields.user_id',Auth::user()->id)
            ->first();
        $plans=$result->plans;
        return view('coachArea.requests.create',compact('questions','plans'));
    }

    /*store new user*/
    public function store(Request $request)
    {
        $this->validation($request);
        $data = $request->all();
        $questions=$data['questions'];
        $plans=$data['plans'];
        $data['password'] = Hash::make(rand(1,100));
        $Date= date('Y-m-d', substr($data['birth_day'], 0, -3));
        $time=$data['appt'];
        $DateTime=$Date.' '.$time.':00';
        $user=array(
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' =>$data['last_name'],
            'password' =>$data['password'],
            'created_at' => $DateTime
        );
        $newUser=User::create($user);
        if($newUser){
            $newUser->roles()->sync(array_values([5]));
        }
        $this->createRequest($newUser->id,$DateTime,$questions,$plans);
        if ($newUser) {
            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.user.singular')]));
            return redirect()->route('CoachArea.requests.create');
        }
        Session::flash('alert-info', 'success,' . __('mm.popup.add.error', ['name' => __('mm.user.singular')]));
        return redirect()->back();
    }
    /*
     *
     *
     */
    public function createRequest($userId,$Date,$questions,$plans_request)
    {
        $coachId=Auth::user()->id;
        $plans = array('items' => []);
        foreach ($plans_request as $item) {
            $plan = Plan::find($item);
            $price = Price::where('plan_id', $plan->id)->where('user_id', $coachId)->first();
            array_push($plans['items'], [
                'plan_id' => $plan->id,
                'price' => $price->price,
                'title' => $plan->title,
                'type' => $plan->type,
            ]);
        }
        $Request=array(
            'user_id' => $userId,
            'coach_id' => Auth::user()->id,
            'total_price' =>0,
            'payment_status' => 'INIT',
            'payment_type' => 'OTHER',
            'program_status' => 0,
            'questions' =>   json_encode(array_values($questions)),
            'plans' =>  $plans,
            'description' => '',
            'created_at'=>$Date,
            'tracking_code' => 1,

        );
        \App\Models\Request::create($Request);
    }

    public function show($id)
    {
        $request = \App\Models\Request::findOrFail($id);
        $typeQuestion=gettype($request->questions);
        $addable=Addable::where('addable_id',$id)
            ->get();
        return view('coachArea.requests.show', [
            'request' => $request,
            'addable' => $addable,
            'typeQuestion'=>$typeQuestion
        ]);
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        //
        // Log::emergency($id);
        $record=\App\Models\Request::where('id', $id)->first();
        if (\App\Models\Request::destroy($id)) {
            $trackFile=public_path() . '/uploads/request/' . $record->tracking_code;
            $this->rrmdir($trackFile);
            if(file_exists ($trackFile.'.zip')){
                unlink($trackFile.'.zip');
            }
            die(true);
        }
        else{
            die(false);
        }
    }
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                        rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                    else
                        unlink($dir. DIRECTORY_SEPARATOR .$object);
                }
            }
            rmdir($dir);
        }
    }

    protected function formatDate($date, $format = 'j F Y، H:i')
    {
        return Verta::createTimestamp(strtotime($date))->format($format);
    }

    public function downloadRequestFile($tracking_code)
    {
        /* todo: correct download link. public or private ? */
        $file_path = public_path('uploads/request/' . $tracking_code . '.' . 'zip');

        if (is_file($file_path)) {
            return response()->download($file_path);
        }

        return redirect()->back();
    }

    public function changeProgramStatus(Request $request)
    {
        $data = [];
        $id = $request->id;
        $program_status = $request->program_status;


        if ($program_status == -1) {
            $request->validate([
                'coach_description' => 'required|string'
            ]);
        }

        $Request = \App\Models\Request::findOrFail($id);

        if ($program_status == -1) {
            $Request->program_status = $program_status;
            $Request->coach_description = $request->coach_description;
            $update = $Request->update();
        } else {
            $update = $Request->update([
                'program_status' => $program_status,
                'updated_at'=> date("Y-m-d H:i:s")
            ]);
        }


        // send email message to user. content of email??
        $email_data = [];
        $email_data['message'] = Auth::user()->voice->message ?? '';
        if ($program_status != -1 && $program_status != 0) {
            //Mail::to(Auth::user()->email)->send(new RequestStatusChange($email_data));
        }

        $data['message'] = __('mm.public.programStatusChangedTo', ['status' => __('mm.programStatuses')[$Request->program_status]]);

        if ($update) {
            $data['success'] = 1;

            switch ($Request->program_status) {
                case 1:
                    if (!is_null(Auth::user()->voice)) {
                        //$this->sendTextMessage((string)$Request->user->mobile, Auth::user()->voice->message);
                    }
                    break;
                case 2:
                    if (!is_null(Auth::user()->voice)) {
                        $this->sendVoiceMessage((string)$Request->user->mobile, Auth::user()->voice->voice_key);
                    }
                    break;
                default:
                    break;
            }

        } else {
            $data['success'] = 0;
        }

        return response()->json($data);
    }

    private function sendTextMessage($mobile_number, $message)
    {
        $bitel = new Bitel();

        $bitel->sendSMS($mobile_number, $message);
    }

    private function sendVoiceMessage($mobile_number, $voice_id)
    {
        $url = "https://ippanel.com/services.jspd";
        $rcpt_nm = array($mobile_number);
        $param = array
        (
            'uname' => 'sms690',
            'pass' => '091214354670',
            'repeat' => '1',
            'to' => json_encode($rcpt_nm),
            'fileUrl' => $voice_id,
            'op' => 'sendvoice'
        );

        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_POSTFIELDS, $param);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response2 = curl_exec($handler);

        $response2 = json_decode($response2);
        $res_code = $response2[0];
        $res_data = $response2[1];
        return true;
    }

    protected function arabicNumbers($string)
    {
        $indian1 = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $indian2 = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١', '٠'];
        $numbers = range(0, 9);
        $convertedIndian1 = str_replace($indian1, $numbers, $string);
        $englishNumbers = str_replace($indian2, $numbers, $convertedIndian1);

        return $englishNumbers;
    }

    public function updateProgramStatus(Request $request)
    {
        $update = \App\Models\Request::whereIn('id', $request->post('requestId'))->update(['program_status' => 1]);
        if ($update) {
            return ('success');
        } else {
            return ('fail');
        }

    }
}
