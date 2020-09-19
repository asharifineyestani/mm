<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;
    public function showLoginForm()
    {
        if(!session()->has("url.intended")){
            session(["url.intended"=>url()->previous()]);
        }
        return redirect()->route('register');
    }
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
        // check if user active (if exists)
        $user = User::where('email', $request->email)->first();
        if (!is_null($user)) {
            if ($user->isRole('coach')) {
                return true;
            }
            else {
                if ($user->status <= 0){
                    $error = ValidationException::withMessages([
                        'email' => [__('mm.user.user_is_not_active_message')]
                    ]);
                    throw $error;
                }
            }
        }
    }
}
