<?php

namespace App\Http\Controllers\Emails;

use App\Helpers\EmailAdapter;
use App\Helpers\Sh4Helper;
use App\Http\Controllers\Controller;
use App\Mail\SendUserMail;
use App\Models\Coach;
use App\Models\DiscountCode;
use App\Models\Email;
use App\Models\Request;
use App\User;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use DB;
use SoapClient;

class TestController extends Controller
{


    public function index()
    {
//        $email = new EmailAdapter();
////        return $email->set($id);
////        return $email->set($id)->adapt();
//        return $email->set($id)->get();
    }



    public function show($id)
    {


        $email = new EmailAdapter();
//        return $email->set($id);
//        return $email->set($id)->adapt();
        return $email->set($id)->adapt()->get();
    }

    public function testLink()
    {
        $ali = '[Link]
Hyper=<a href="https://mokameleman.com?action=sharecart&p_id[0]=2385&qnt[0]=1&v_id[0]=2388&vars[0][attribute_%d9%88%d8%b2%d9%86]=??? ???&vars[0][attribute_%d8%b7%d8%b9%d9%85]=???? ???????"target="_blank">ÓÈÏ ÎÑíÏ ããá ÔãÇ</a>';

        return json_encode($ali);
    }

    public function setUserId()
    {
        DB::table('emails')->update(['user_id' => Auth::user()->id]);
    }


    public function updateEmails()
    {
        $items = Program::orderBy('id', 'Desc')->limit(10)->get();
        foreach ($items as $item) {
            $this->updateEmail($item->id);
        }

        $this->setUserId();

    }

    public function updateEmail($id)
    {
        $email = new EmailAdapter();
//        return $email->set($id);
//        return $email->set($id)->adapt();
        return $email->set($id)->adapt()->create();
    }


    public function showEmail($id)
    {
        return Email::where('log_id',$id)->first();
    }
}
