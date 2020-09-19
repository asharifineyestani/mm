<?php

namespace App\Http\Controllers\Emails;

use App\Helpers\EmailAdapter;
use App\Models\Adapted;
use App\Models\Received;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class AdaptedController extends Controller
{
    //
    public function show($id)
    {

        return Adapted::where('log_id', $id)->first();

        $email = new EmailAdapter();
        return $email->set($id)->adapt()->create();
        return $email->set($id)->adapt();
    }


    public function doAdapt($id)
    {
        $email = new EmailAdapter();
//        return $email->set($id)->adapt()->get();
//        return $email->set($id)->adapt()->get()['workout']['Thursday'][5];
        return $email->set($id)->adapt()->create();
    }


    public function update($id)
    {
        $email = new EmailAdapter();
         $email->set($id)->adapt()->create();

         echo 'updated';
    }


    public function updateAll()
    {
        $adapteds = Adapted::all();


        foreach ($adapteds as $adapted) {
            $this->update($adapted->log_id);
        }

    }


    public function showBeforeUpdate($id)
    {
        $email = new EmailAdapter();
        return $email->set($id)->adapt()->get();
    }

    public function showOriginal($id)
    {

        return Received::find($id);
    }




    public function allUserWorkout($id)
    {

        return Received::find($id);
    }
}
