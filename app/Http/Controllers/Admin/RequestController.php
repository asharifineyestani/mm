<?php

namespace App\Http\Controllers\Admin;


use App\Mail\RequestStatusChange;
use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\Builder\DefaultUuidBuilder;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Body;
use App\Models\Addable;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    public $userRate =1;
    public function index()
    {
        $userRate = Input::get('userRate');
        return view('admin.requests.index',compact('userRate'));
    }

    public function getRequests(Request $request)
    {
        $query = \App\Models\Request::leftJoin('users as users', 'requests.user_id', '=', 'users.id')
            ->leftJoin('users as coaches', 'requests.coach_id', '=', 'coaches.id')
            ->select(DB::raw(
                'requests.id as id,' .
                'CONCAT(users.first_name, " ", users.last_name) as name,' .
                'users.email as user_email,' .
                'users.mobile as user_mobile,' .
                'CONCAT(coaches.first_name, " ", coaches.last_name) as coach,' .
                'requests.tracking_code as tracking_code,' .
                'requests.created_at as request_date,' .
                'requests.program_status as p_status,' .
                'requests.payment_type as p_type,' .
                'requests.tracking_code as tracking_code,requests.score as Score'
            ))
            ->where(function ($q) {
                $q->where('requests.payment_status', 'SUCCEED')
                    ->orWhere('requests.payment_type', 'OTHER');
            });
        $results = datatables($query)
            ->editColumn('request_date', function ($request){
                return '<span class="small">' . $this->formatDate($request->request_date) . '</span>';
            })
            ->addColumn('operation', function ($request){
                return view('admin.requests.partials._operation', [
                    'request_id' => $request->id,
                    'tracking_code' => $request->tracking_code,
                ]);
            })
            ->addColumn('program_status', function ($request){
                return view('coachArea.partials._program_status', [
                    'p_status' => $request->p_status,
                    'request_id' => $request->id
                ]);
            })
            ->addColumn('checkbox', function ($request){
                return view('coachArea.partials._input_check_box', [
                    'request_id' => $request->id
                ]);
            })


            ->filterColumn('name', function ($query, $keyword){
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, '%' . $keyword . '%');
            })
            ->filterColumn('coach', function ($query, $keyword){
                $sql = 'CONCAT(coaches.first_name, " ", coaches.last_name) like ?';
                $query->whereRaw($sql, '%' . $keyword . '%');
            })
            ->filterColumn('request_date', function ($query, $keyword) {
                $date = array_map('intval', explode('/', $this->arabicNumbers($keyword)));
                $date_g = verta()->setDate($date[0], $date[1], $date[2])->formatGregorian('Y-m-d');
                $query->whereDate('requests.created_at', $date_g);
            })
            ->filterColumn('program_status', function ($query, $keyword){
                $query->where('requests.program_status', $keyword);
            })
            ->editColumn('p_type', function ($request){
                return __('mm.payment_types.' . $request->p_type);
            })
            ->rawColumns(['operation', 'program_status','request_date','checkbox'])
            ->make(true);

        return $results;
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        $request = \App\Models\Request::findOrFail($id);
        $typeQuestion=gettype($request->questions);
        $addable=Addable::where('addable_id',$id)
            ->get();
        return view('admin.requests.show', [
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
        if (is_file($file_path)){
            return response()->download($file_path);
        }
        return redirect()->back();
    }

    public function changeProgramStatus(Request $request)
    {
        $data = [];
        $id = $request->id;
        $program_status = $request->program_status;
        if ($program_status == -1){
            $request->validate([
                'coach_description' => 'required|string'
            ]);
        }
        $Request = \App\Models\Request::findOrFail($id);
        if ($program_status == -1){
            $Request->program_status = $program_status;
            $Request->coach_description = $request->coach_description;
            $update = $Request->update();
        }
        else
        {
            $update = $Request->update([
                'program_status' => $program_status,
                'updated_at'=> date("Y-m-d H:i:s")
            ]);
        }
        // send email message to user. content of email??
        $email_data = [];
        $email_data['message'] = $Request->coach->voice->message ?? '';
        if ($program_status != -1 && $program_status != 0){
            //Mail::to($Request->user->email)->send(new RequestStatusChange($email_data));
        }
        $data['message'] = __('mm.public.programStatusChangedTo', ['status' => __('mm.programStatuses')[$Request->program_status]]);
        if ($update){
            $data['success'] = 1;
            switch ($Request->program_status){
                case 1:
                    if (!is_null($Request->coach->voice)){
                        //$this->sendTextMessage((string)$Request->user->mobile, $Request->coach->voice->message);
                      //  $this->sendVoiceMessage((string)$Request->user->mobile, $Request->coach->voice->voice_key);
                    }
                    break;
                case 2:
                    if (!is_null($Request->coach->voice)){
                        $this->sendVoiceMessage((string)$Request->user->mobile, $Request->coach->voice->voice_key);
                    }
                    break;
                default:
                    break;
            }

        }else{
            $data['success'] = 0;
        }
        return response()->json($data);
    }


    private function sendVoiceMessage($mobile_number, $voice_id)
    {
        $url = "https://ippanel.com/services.jspd";
        $rcpt_nm = array($mobile_number);
        $param = array
        (
            'uname'=>'sms690',
            'pass'=>'091214354670',
            'repeat'=>'1',
            'to'=>json_encode($rcpt_nm),
            'fileUrl' =>$voice_id,
            'op'=>'sendvoice'
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
    public function updateProgramStatus(Request $request){
        $update=\App\Models\Request::whereIn('id',$request->post('requestId'))->update(['program_status' => 1]);
        if($update){
            return('success');
        }
        else{
            return('fail');
        }
    }

}
