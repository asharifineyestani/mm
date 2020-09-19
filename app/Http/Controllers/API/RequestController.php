<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\PriceController;
use App\Http\Requests\OrderRequest;
use App\Models\Addable;
use App\Models\Ads;
use App\Sh4\RequestHelper;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\DiscountCode;

use App\Models\Body;

use App\Models\PlanItem;
use App\Models\Province;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

use DB;
use com\grandt\php\LipsumGenerator;

use App\Mail\SendUserMail;

use Illuminate\Support\Facades\Validator;
use App\Facades\ResultData as Result;

use Illuminate\Support\Facades\Log;


class RequestController extends Controller
{
    use RequestHelper;

    public $prefix = 'body';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //


        $data = \App\Models\Request::orderBy('id', 'desc')->get();


        return Result::setData($data)->get();

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(OrderRequest $request)
    {

        Log::alert($request->all()); #todo test

        #fields
        $fields = $request->only(['coach_id', 'payment_type', 'description', 'questions', 'plans']);
        $fields['user_id'] = Auth::user()->id;
        $fields['payment_status'] = 'INIT';
        $fields['program_status'] = 0;
        $fields['tracking_code'] = 1;

        if ($fields['description'] == null)
            $fields['description'] = 'ONLINE';

        #fields[discount_percent]
        $discountCode = $request->get('discount');
        $discount = new DiscountCode();
        $fields['discount_percent'] = $discount->getPercentDiscountFromCode($discountCode, $fields['coach_id'])->percent;

        #fields[plans , total_price]
        $planItems = new PlanItem();
        $planItems->setCoachId($fields['coach_id']);
        $planItems->setPlanIds($fields['plans']);
        $fields['plans'] = $planItems->all();
        $fields['total_price'] = PriceController::total($planItems);

        #body
        $body = $request->get('body');
        $body['user_id'] = Auth::user()->id;

        #Insert Body
        $body = new Body($body);
        $body->save();

        #Insert Request
        $newRequest = new \App\Models\Request($fields);
        $newRequest->save();


        $media_paths = $request->get('media_paths');

        if (Input::has('media_paths'))
            foreach ($media_paths as $path) {
                $addable = Addable::where('media_path', $path)->first();
                $addable->update([
                    'addable_id' => $newRequest->id,
                    'addable_type' => Body::class,
                ]);

                $this->mediaPaths[] = $path;
            }


        /*Send Email*/


        $this->emailHandler($newRequest->id);


        $data['request'] = $newRequest;
        return Result::setData($data)->get();

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //

        $request = \App\Models\Request::find($id);


        return $request;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function tracking($trackingCode)
    {
        $sliders = Ads::all();


        $order = \App\Models\Request::with('coach')->where('tracking_code', $trackingCode)->latest()->first();


//        return $order;
        if (isset($order)) {
            if ($order->program_status > 0) {
                $message = 'برنامه شما با موفقیت ارسال شده است';
                $message_body = 'پیام مربی: ' . $order->coach_description;
                $color = 'green';

                return view('user.pages.tracking-message', compact('message', 'message_body', 'color', 'sliders'));
            }
            elseif ($order->program_status < 0) {
                $message = 'پرونده شما دارای مشکل است ';
                $message_body = 'پیام مربی: ' . $order->coach_description;
                $color = '#f43e3e';

                return view('user.pages.tracking-message', compact('message', 'message_body', 'color', 'sliders'));
            }
            else {
                $orders = DB::table('requests')->where([
                    ['requests.id', '<=', $order->id],
                    ['requests.coach_id', '=', $order->coach_id],
                    ['requests.program_status', '=', 0]
                ])->join('users', 'requests.user_id', '=', 'users.id')
                    ->select('users.first_name', 'users.last_name', 'users.email', 'requests.*')
                    ->orderBy('requests.id', 'asc')->get();
                $coach = DB::table('coach_fields')->where('user_id', $order->coach_id)->get()->first();

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
                ])->count();
                if ($coach->program_per_day != 0) {
                    $days_before_get = ceil($count / $coach->program_per_day);

                } elseif ($coach->program_per_day == 0) {
                    $days_before_get = NULL;

                }
                return view('user.pages.tracking-table-api', compact('order', 'orders', 'days_before_get', 'sliders'));
            }
        }
        else {

            $error_message = 'این کد در سیستم موجود نمی باشد';
//            return view('user.pages.client_tracking',compact('erro_message'));
        }

    }
}
