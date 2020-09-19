<?php

namespace App\Http\Controllers\API;

use App\Helpers\EmailAdapter;
use App\Models\Email;
use App\Sh4\RequestHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use DB;

class TestController extends Controller
{



    public function updateAll()
    {

        $email = new EmailAdapter();
        return $email->updateAll();
    }

    public function updateEmail($id = 'me')
    {
        if ($id == 'me')
            $id = Auth::user()->id;

        DB::table('emails')->update(['user_id' => $id]);

        return 'All of emails user_id set to :' . $id;
    }


    public function showEmail($id)
    {
        return Email::find($id);
    }


    public function showAdapt($id)
    {
        $email = new EmailAdapter();
        return $email->set($id)->adapt()->get();
    }

}
