<?php

namespace App\Http\Controllers\Dev;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Dev\User;

class UserController extends Controller
{
    //

    public function index()
    {
        return User::limit(10)->get();
    }
}
