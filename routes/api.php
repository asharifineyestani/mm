<?php

use Illuminate\Http\Request;



Route::get('/off', function () {
    $exitCode[] = Artisan::call('down');
});
Route::get('/up', function () {
    $exitCode[] = Artisan::call('up');
});

/*
|--------------------------------------------------------------------------
| Emails
|--------------------------------------------------------------------------
|
*/
Route::group([
    'namespace'  => 'Emails',
    'prefix'     => 'emails',
], function ($router) {
    require base_path('routes/emails.php');
});



Route::group([
    'namespace'  => 'Dev',
    'prefix'     => 'dev',
], function ($router) {
    require base_path('routes/dev.php');
});



/*
|--------------------------------------------------------------------------
| Payment
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    Route::any('/payments/INCREASE_CREDIT/callback', 'API\PaymentController@callbackIncrease');
    Route::any('/payments/{paymentable_type}/callback', 'API\PaymentController@callback');
    Route::any('/payments', 'API\PaymentController@handleGetRequest');

    Route::resource('podcasts', 'API\PodcastController'); #todo: destroy must remove
    Route::get('/requests/tracking/{tracking_code}','API\RequestController@tracking');
});


/*
|--------------------------------------------------------------------------
| Testing
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
//    Route::get('tests/emails/updateUserId/me', 'API\TestController@updateEmail');

    Route::get('/testPost',function(){
        return 9;
    });


});



Route::prefix('v1')->group(function () {
    Route::get('tests/emails/updateAll', 'API\TestController@updateAll');
    Route::get('tests/emails/{id}', 'API\TestController@showEmail');
    Route::get('tests/emails/updateUserId/{id}', 'API\TestController@updateEmail');
    Route::get('tests/adapts/{id}', 'API\TestController@showAdapt');


    Route::get('run-seeder/', function () {

        Artisan::call("db:seed");
    });
});


Route::prefix('v1')->group(function () {


    Route::resource('posts', 'API\PostController')->only([
        'index', 'show'
    ]);

    Route::resource('cities', 'API\CityController');


    Route::get('config', 'API\ConfigController@index');



    Route::get('users/sample/{id}/workout', 'API\UserController@workout');
    Route::get('users/sample/{id}/nutrition', 'API\UserController@nutrition');
    Route::get('users/sample/{id}/analyze', 'API\UserController@analyze');
    Route::get('users/sample/{id}/sounds', 'API\UserController@sounds');


});


/*
|--------------------------------------------------------------------------
| endpoints @todo passed
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:api')->group(function () {

//    Route::get('analyses', 'API\AnalysisController@index');


    Route::put('bodies/current', 'API\BodyController@update');
    Route::get('bodies/current', 'API\BodyController@show');
    Route::resource('users', 'API\UserController'); #todo: destroy must remove
    Route::get('users/{id}/workout', 'API\UserController@workout');
    Route::get('users/{id}/nutrition', 'API\UserController@nutrition');
    Route::get('users/{id}/analyze', 'API\UserController@analyze');
    Route::get('users/{id}/sounds', 'API\UserController@sounds');

    Route::get('users/{id}/requests', 'API\UserController@myRequests');

    Route::resource('coaches', 'API\CoachController');
    Route::get('coaches/plans', 'API\CoachController@plans'); # @todo problem


    Route::post('media', 'API\MediaController@store');

    Route::resource('requests', 'API\RequestController');




    Route::resource('questions', 'API\QuestionController');

    Route::get('home', 'API\HomeController@index');


    #profile
    Route::resource('users', 'API\UserController'); #todo: destroy must remove
    Route::get('profile', 'API\UserController@profile'); // todo test
    Route::put('profile', 'API\UserController@updateProfile');
});


/*
|--------------------------------------------------------------------------
| Authentication @passed @todo check Hashing system
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    #forget password
    Route::group([
        'namespace' => 'Auth',
        'middleware' => 'api',
        'prefix' => 'password'
    ], function () {
        Route::post('create', 'PasswordResetController@create');
//        Route::post('create', 'ForgotPasswordController@sendResetLinkEmail');
        Route::get('find/{token}', 'PasswordResetController@find');
        Route::post('reset', 'PasswordResetController@reset');
    });

    #sms
//    Route::post('auth/sms/mobile', 'API\UserController@getMobile'); #step1 [mobile]
//    Route::post('auth/sms/code', 'API\UserController@getCode'); // #step2 [mobile]  Login or Register
//    Route::post('auth/register', 'API\UserController@registerWithCode'); #step3 [mobile]
//    Route::post('auth/password/login', 'API\UserController@loginWithPassword'); #login [mobile]
    #email
    Route::post('auth/email', 'API\UserController@getEmail'); #step1 [email]
    Route::post('auth/active/code', 'API\UserController@activeWithCode'); // #step2 [email]
    Route::post('auth/email/register', 'API\UserController@registerEmail'); // #step2 [email]
    Route::post('auth/password/login', 'API\UserController@loginWithPassword'); #login [mobile]

});


/*
|--------------------------------------------------------------------------
| Testing @todo passed
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::get('logs', 'API\UserController@logs'); #todo: destroy must remove
    Route::resource('test', 'TestController'); // todo test
});


/*
|--------------------------------------------------------------------------
| ???
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
