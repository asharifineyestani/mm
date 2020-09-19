<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\GatewayTransaction;
use App\Models\Request;
use App\Sh4\RequestHelper;
use App\User;
use Illuminate\Support\Facades\Auth;
use Gateway;
use Illuminate\Support\Facades\Input;
use Larabookir\Gateway\Mellat\Mellat;
use Larabookir\Gateway\Pasargad\Pasargad;
use Larabookir\Gateway\Zarinpal\Zarinpal;

class PaymentController extends Controller
{
    use RequestHelper;


    public $callback;
    public $paymentType;
    public $requestId;
    public $request;
    public $totalPrice;
    public $user;
    public $discountCode;
    public $isApi = false;

    public function handleGetRequest()
    {
        $this->discountCode = Input::get('discount_code');
        $this->paymentType = Input::get('payment_type');
        $this->requestId = Input::get('request_id');
        return $this->payment();
//        return $this->request = Request::where('id', $this->requestId)->first();
    }

    private function payment()
    {
        if ($this->requestId && ($this->paymentType == 'ONLINE' || $this->paymentType == 'CREDIT'))
            $this->initRequest();
        elseif ($this->paymentType == 'INCREASE_CREDIT')
            $this->initIncrease();

        return $this->selectPayment();

    }

    private function initRequest()
    {
        $this->request = Request::where('id', $this->requestId)->first();
        if ($this->isApi)
            $this->callback = env('APP_URL') . '/api/v1/payments/' . $this->paymentType . '/callback?request_id=' . $this->requestId;
        else
            $this->callback = env('APP_URL') . '/payments/' . $this->paymentType . '/callback?request_id=' . $this->requestId;
        $this->totalPrice = $this->calculatePrice($this->request);
        $this->user = User::find($this->request->user_id);
    }

    private function initIncrease()
    {
        $this->callback = env('APP_URL') . '/payments/' . $this->paymentType . '/callback';
        $this->totalPrice = Input::get('price');
        $this->user = Auth::user();
    }

    private function calculatePrice(Request $request)
    {
        $discount = new DiscountCode();
        $discount = $discount->getPercentDiscountFromCode($this->discountCode, $request->coach_id);
        $percent = ($discount) ? $discount->percent : 0;
        //todo sh4 this is not safe (!)
        $price = $request->total_price - ($percent * $request->total_price / 100);
        return $price;
    }

    private function selectPayment()
    {
        if ($this->request)
            switch ($this->paymentType) {
                case "CREDIT":
                    $user = $this->user;
                    if ($user->balance >= $this->totalPrice) {
                        $user->withdraw($this->totalPrice, 'WITHDRAW_BUY_CREDIT', $this->request->id, ['description' => 'buy from credit']);
                        Request::where('id', $this->requestId)->update(['payment_status' => 'SUCCEED']);

                        return view('user.pages.end-request')->with([
                            'tracking_code' => $this->request->tracking_code
                        ]);

                    } else {
                        echo 'Credit is not enough';
                        return back();
                    }
                    break;
                case "ONLINE":
                    $gateway = Gateway::make(new Pasargad());
                    return $this->doPayment($gateway);
                    break;
                case "OTHER":
                    break;
                default:
                    echo "Payment Type is Wrong!";
            }
        elseif ($this->paymentType == 'INCREASE_CREDIT') {
            $gateway = Gateway::make(new Pasargad());
            return $this->doPayment($gateway);
        }

    }

    private function doPayment($gateway)
    {

        try {

            try {
                $gateway->setCallback(url($this->getCallback()));
                $gateway->price($this->totalPrice * 10)->ready();
                $order['user_id'] = $this->user->id;
                $order['request_id'] = $this->requestId;
                GatewayTransaction::find($gateway->transactionId())->update($order);

                return $gateway->redirect();

            } catch (\Exception $e) {
                echo $e->getMessage();
            }

        } catch (\Exception $e) {

            echo $e->getMessage();
        }

    }

    private function getCallback()
    {
        return $this->callback;
    }

    public function callback($paymentable_type)
    {

        $this->requestId = Input::get('request_id');
        $transactionId = Input::get('transaction_id');

        if (Input::get('iN'))
            $transactionId = Input::get('iN');

        if ($this->requestId) {
            $this->request = Request::find($this->requestId);
            $this->user = User::find($this->request->user_id);
        }
        $transaction = GatewayTransaction::find($transactionId);
        $user = $this->user;


        try {
            $gateway = \Gateway::verify();
            $trackingCode = $gateway->trackingCode();
            $refId = $gateway->refId();
            $cardNumber = $gateway->cardNumber();
            if ($paymentable_type == "ONLINE") {
                $user->deposit(($transaction->price)/10, 'DEPOSIT_DIRECT_BUY', $transactionId, ['description' => 'افزایش موجودی بابت خرید آنلاین درخواست', 'request_id' => $this->requestId]);
                $user->withdraw(($transaction->price)/10, 'WITHDRAW_BUY_ONLINE', $this->requestId, ['description' => 'برداشت بابت خرید آنلاین درخواست ']);
                Request::where('id', $this->requestId)->update(['payment_status' => 'SUCCEED']);

                /**
                 * deposit reward for reagent
                 */
                $request = $this->request;
                $user = $request->user;
                $reagent = $request->user->reagent;

                if ($reagent && $reagent->CheckQualifyForReward($this->requestId))
                    $reagent->deposit($this->reagentReward, 'DEPOSIT_REWARD', $request->id, ['description' => 'Introducing the site to friends', 'user_id' => $user->id, 'request_id' => $request->id]);

                if ($this->isApi) {
                    $this->emailHandler($this->requestId, 'CREATE_REQUEST_IN_PAYMENT');
                    return view('callback/success')->with([
                        'tracking_code' => $this->request->tracking_code,
                        'card_number' => $gateway->cardNumber(),
                    ]);
                }

                /*Added By Ayeeni*/
               $requestUser=new RequestController();
               $requestUser->sendEmail($this->request->tracking_code);
               $requestUser->smsConfiguration($this->request->tracking_code);


                //$this->emailHandler($this->requestId, 'CREATE_REQUEST_IN_PAYMENT');

                return view('user.pages.end-request')->with([
                    'tracking_code' => $this->request->tracking_code
                ]);
                $site_message = 'پرداخت با موفقیت انجام شد کد پیگری شما ' . $this->request->tracking_code;
                //online

            } elseif ($paymentable_type == "INCREASE_CREDIT") {

                Auth::user()->deposit($transaction->price / 10, 'DEPOSIT_INCREASE_CREDIT', $transactionId, ['description' => 'افزایش موجودی بابت افزایش اعتبار توسط کاربر']);
                //increase
                $site_message = 'شارژ انجام شد برای ادامه خرید به صفحه درخواست برگردید';
            }


            return view('user.pages.getMessage')->with([
                'site_message' => $site_message
            ]);

        } catch (\Larabookir\Gateway\Exceptions\RetryException $e) {
            // تراکنش قبلا سمت بانک تاییده شده است و
            // کاربر احتمالا صفحه را مجددا رفرش کرده است
            // لذا تنها فاکتور خرید قبل را مجدد به کاربر نمایش میدهیم
            // vu failed

            if ($this->isApi) {
                return view('callback/repeat');
            }


        } catch (\Exception $e) {

            // vu cansel

            if ($paymentable_type == "ONLINE") {
                Request::where('id', $this->requestId)->update(['payment_status' => 'FAILED']);
            }
            // نمایش خطای بانک


            if ($this->isApi) {
                return view('callback/cancel');
            } else {
                return view('user.pages.Error_Payment');
            }


            // echo $e->getMessage();
        }


    }

    public function callbackOnline($paymentable_type)
    {

        $this->requestId = Input::get('request_id');
        $transactionId = Input::get('transaction_id');

        if (Input::get('iN'))
            $transactionId = Input::get('iN');

        if ($this->requestId) {
            $this->request = Request::find($this->requestId);
            $this->user = User::find($this->request->user_id);
        }
        $transaction = GatewayTransaction::find($transactionId);
        $user = $this->user;


        try {
            $gateway = \Gateway::verify();
            $trackingCode = $gateway->trackingCode();
            $refId = $gateway->refId();
            $cardNumber = $gateway->cardNumber();
            if ($paymentable_type == "ONLINE") {
                $user->deposit(($transaction->price)/10, 'DEPOSIT_DIRECT_BUY', $transactionId, ['description' => 'افزایش موجودی بابت خرید آنلاین درخواست', 'request_id' => $this->requestId]);
                $user->withdraw(($transaction->price)/10, 'WITHDRAW_BUY_ONLINE', $this->requestId, ['description' => 'برداشت بابت خرید آنلاین درخواست ']);
                Request::where('id', $this->requestId)->update(['payment_status' => 'SUCCEED']);

                /**
                 * deposit reward for reagent
                 */
                $request = $this->request;
                $user = $request->user;
                $reagent = $request->user->reagent;

                if ($reagent && $reagent->CheckQualifyForReward($this->requestId))
                    $reagent->deposit($this->reagentReward, 'DEPOSIT_REWARD', $request->id, ['description' => 'Introducing the site to friends', 'user_id' => $user->id, 'request_id' => $request->id]);

                if ($this->isApi) {
                    $this->emailHandler($this->requestId, 'CREATE_REQUEST_IN_PAYMENT');
                    return view('callback/success')->with([
                        'tracking_code' => $this->request->tracking_code,
                        'card_number' => $gateway->cardNumber(),
                    ]);
                }

                /*Added By Ayeeni*/
                $requestUser=new RequestController();
                $requestUser->sendEmail($this->request->tracking_code);
                $requestUser->smsConfiguration($this->request->tracking_code);


                //$this->emailHandler($this->requestId, 'CREATE_REQUEST_IN_PAYMENT');

                return view('user.pages.end-request')->with([
                    'tracking_code' => $this->request->tracking_code
                ]);
                $site_message = 'پرداخت با موفقیت انجام شد کد پیگری شما ' . $this->request->tracking_code;
                //online

            } elseif ($paymentable_type == "INCREASE_CREDIT") {

                Auth::user()->deposit($transaction->price / 10, 'DEPOSIT_INCREASE_CREDIT', $transactionId, ['description' => 'افزایش موجودی بابت افزایش اعتبار توسط کاربر']);
                //increase
                $site_message = 'شارژ انجام شد برای ادامه خرید به صفحه درخواست برگردید';
            }


            return view('user.pages.getMessage')->with([
                'site_message' => $site_message
            ]);

        } catch (\Larabookir\Gateway\Exceptions\RetryException $e) {
            // تراکنش قبلا سمت بانک تاییده شده است و
            // کاربر احتمالا صفحه را مجددا رفرش کرده است
            // لذا تنها فاکتور خرید قبل را مجدد به کاربر نمایش میدهیم
            // vu failed

            if ($this->isApi) {
                return view('callback/repeat');
            }


        } catch (\Exception $e) {

            // vu cansel

            if ($paymentable_type == "ONLINE") {
                Request::where('id', $this->requestId)->update(['payment_status' => 'FAILED']);
            }
            // نمایش خطای بانک


            if ($this->isApi) {
                return view('callback/cancel');
            } else {
                return view('user.pages.Error_Payment');
            }


            // echo $e->getMessage();
        }


    }

    public function callbackIncrease($paymentable_type)
    {

        return 'credit';
        $this->requestId = Input::get('request_id');
        $transactionId = Input::get('transaction_id');

        if (Input::get('iN'))
            $transactionId = Input::get('iN');

        if ($this->requestId) {
            $this->request = Request::find($this->requestId);
            $this->user = User::find($this->request->user_id);
        }
        $transaction = GatewayTransaction::find($transactionId);
        $user = $this->user;


        try {
            $gateway = \Gateway::verify();
            $trackingCode = $gateway->trackingCode();
            $refId = $gateway->refId();
            $cardNumber = $gateway->cardNumber();
            if ($paymentable_type == "ONLINE") {
                $user->deposit(($transaction->price)/10, 'DEPOSIT_DIRECT_BUY', $transactionId, ['description' => 'افزایش موجودی بابت خرید آنلاین درخواست', 'request_id' => $this->requestId]);
                $user->withdraw(($transaction->price)/10, 'WITHDRAW_BUY_ONLINE', $this->requestId, ['description' => 'برداشت بابت خرید آنلاین درخواست ']);
                Request::where('id', $this->requestId)->update(['payment_status' => 'SUCCEED']);

                /**
                 * deposit reward for reagent
                 */
                $request = $this->request;
                $user = $request->user;
                $reagent = $request->user->reagent;

                if ($reagent && $reagent->CheckQualifyForReward($this->requestId))
                    $reagent->deposit($this->reagentReward, 'DEPOSIT_REWARD', $request->id, ['description' => 'Introducing the site to friends', 'user_id' => $user->id, 'request_id' => $request->id]);

                if ($this->isApi) {
                    $this->emailHandler($this->requestId, 'CREATE_REQUEST_IN_PAYMENT');
                    return view('callback/success')->with([
                        'tracking_code' => $this->request->tracking_code,
                        'card_number' => $gateway->cardNumber(),
                    ]);
                }

                /*Added By Ayeeni*/
                $requestUser=new RequestController();
                $requestUser->sendEmail($this->request->tracking_code);
                $requestUser->smsConfiguration($this->request->tracking_code);


                //$this->emailHandler($this->requestId, 'CREATE_REQUEST_IN_PAYMENT');

                return view('user.pages.end-request')->with([
                    'tracking_code' => $this->request->tracking_code
                ]);
                $site_message = 'پرداخت با موفقیت انجام شد کد پیگری شما ' . $this->request->tracking_code;
                //online

            } elseif ($paymentable_type == "INCREASE_CREDIT") {

                Auth::user()->deposit($transaction->price / 10, 'DEPOSIT_INCREASE_CREDIT', $transactionId, ['description' => 'افزایش موجودی بابت افزایش اعتبار توسط کاربر']);
                //increase
                $site_message = 'شارژ انجام شد برای ادامه خرید به صفحه درخواست برگردید';
            }


            return view('user.pages.getMessage')->with([
                'site_message' => $site_message
            ]);

        } catch (\Larabookir\Gateway\Exceptions\RetryException $e) {
            // تراکنش قبلا سمت بانک تاییده شده است و
            // کاربر احتمالا صفحه را مجددا رفرش کرده است
            // لذا تنها فاکتور خرید قبل را مجدد به کاربر نمایش میدهیم
            // vu failed

            if ($this->isApi) {
                return view('callback/repeat');
            }


        } catch (\Exception $e) {

            // vu cansel

            if ($paymentable_type == "ONLINE") {
                Request::where('id', $this->requestId)->update(['payment_status' => 'FAILED']);
            }
            // نمایش خطای بانک


            if ($this->isApi) {
                return view('callback/cancel');
            } else {
                return view('user.pages.Error_Payment');
            }


            // echo $e->getMessage();
        }


    }
}
