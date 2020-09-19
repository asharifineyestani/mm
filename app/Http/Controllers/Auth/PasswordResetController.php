<?php
/**
 * Created by PhpStorm.
 * User: ali
 * Date: 7/11/19
 * Time: 1:43 AM
 */

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\User;
use App\Models\PasswordReset;
use App\Facades\ResultData as Result;

class PasswordResetController extends Controller
{
    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request)
    {

        $f = new ForgotPasswordController();


        if($f->sendResetLinkEmail($request)) {
            $data['message'] = 'یک ایمیل حاوی لینک ریست پسورد برای شما ارسال شد.';
            return Result::setData($data)->get();
        }



        return 0;

        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
//            return response()->json([
//                'message' => 'We cant find a user with that e-mail address.'
//            ], 404);


            $data['message'] = 'کاربری با ایمیل فوق وجود ندارد';

            return Result::setErrors($data)->get();
        }


        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
            ]
        );
        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );
//        return response()->json([
//            'message' => 'We have e-mailed your password reset link!'
//        ]);

        $data['message'] = 'یک ایمیل حاوی لینک ریست پسورد برای شما ارسال شد.';

        return Result::setData($data)->get();
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {

        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        if (!$passwordReset) {

//            return response()->json([
//                'message' => 'This password reset token is invalid.'
//            ], 404);

            $data['message'] = 'توکن ریست اشتباه است یا ممکن است منقضی شده باشد';

            return Result::setErrors($data)->get();
        }

        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
//            return response()->json([
//                'message' => 'This password reset token is invalid.'
//            ], 404);

            $data['message'] = 'توکن ریست اشتباه است یا ممکن است منقضی شده باشد';

            return Result::setErrors($data)->get();
        }
//        return response()->json($passwordReset);


        $data = $passwordReset;

        return Result::setData($data)->get();
    }

    /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(PasswordReset $request)
    {


        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();

        if (!$passwordReset) {

//            return response()->json([
//                'message' => 'This password reset token is invalid.'
//            ], 404);

            $data['message'] = 'توکن اشتباه است یا ممکن است منقضی شده باشد';

            return Result::setErrors($data)->get();
        }

        $user = User::where('email', $passwordReset->email)->first();
        if (!$user) {
//            return response()->json([
//                'message' => 'We cant find a user with that e-mail address.'
//            ], 404);


            $data['message'] = 'کاربری با ایمیل فوق وجود ندارد';

            return Result::setErrors($data)->get();
        }

        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess($passwordReset));

//        return response()->json($user);

        $data = $user;

        return Result::setData($data)->get();
    }

}
