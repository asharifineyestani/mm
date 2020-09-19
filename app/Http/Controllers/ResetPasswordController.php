<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use test\Mockery\TraitWithAbstractMethod;
use DB;

class ResetPasswordController extends Controller
{


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);
        $user = User::find(Auth::id());
        $user->password = Hash::make($request->input('password'));
        if ($user->save()) {
            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.public.password')]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('mm.public.password')]));
        return redirect()->back();
    }


    public function resetPassword(Request $request)
    {
        //Validate input
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
            'password' => 'required|confirmed'
        ]);

        //check if input is valid before moving on
        if ($validator->fails()) {
            return redirect()->back()->withErrors(['email' => 'لطفا اطلاعات را با دقت وارد نمایید']);
        }

        $password = $request->password;
        // Validate the token
        $tokenData = DB::table('password_resets')
            ->where('token', $request->token)->first();
        // Redirect the user back to the password reset request form if the token is invalid
        if (!$tokenData)return redirect()->back()->withErrors(['email' => 'توکن منقضی شده است']);

        $user = User::where('email', $tokenData->email)->first();
        // Redirect the user back if the email is invalid
        if (!$user) return redirect()->back()->withErrors(['email' => 'ایمیل پیدا نشد']);
        //Hash and update the new password
        $user->password = \Hash::make($password);
        $user->update(); //or $user->save();

        //login the user immediately they change password successfully

        //Delete the token
        DB::table('password_resets')->where('email', $user->email)
            ->delete();

        //Send Email Reset Success Email
        return 'OK';

    }
}
