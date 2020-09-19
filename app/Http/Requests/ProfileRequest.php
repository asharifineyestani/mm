<?php
/**
 * Created by PhpStorm.
 * User: ali
 * Date: 7/20/19
 * Time: 10:09 AM
 */

namespace App\Http\Requests;


use Illuminate\Support\Facades\Auth;

class ProfileRequest extends UserRequest
{

    public $id;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->id = Auth::user()->id;
    }

}
