<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 *
 * [payments]
 *  ? payment_type = "ONLINE"  & request_id  = {id}     ||   example: http://mm.nikanproject.ir/payments?payment_type=ONLINE&request_id=45
 *  ? payment_type ="CREDIT" & request_id  = {id}       ||
 *  ? payment_type ="INCREASE_CREDIT" &  price = 1000  https://new.morabiman.com/payments?payment_type=INCREASE_CREDIT&price=1000 ||
 *
 * [callback]
 * {paymentable_type} = "BUY_REQUEST" ? request_id = {id} ||
 * {paymentable_type} = "INCREASE_CREDIT"
 *
 */

use Illuminate\Support\Facades\Artisan;

Route::get('/pasargad', 'TestController@pasargad');
Route::get('/pasargad/callback', 'TestController@callback');


Route::get('/modifyBadRequest', 'Controller@modifyBadRequest');
Route::any('/payments/INCREASE_CREDIT/callback', 'PaymentController@callbackIncrease');
Route::any('/payments/{paymentable_type}/callback', 'PaymentController@callback');
Route::any('/payments', 'PaymentController@handleGetRequest');

/**
 * Ajax
 *
 */
Route::prefix('ajx')->name('ajx.')->group(function () {
    Route::prefix('table')->name('table.')->group(function () {
        Route::post('discounts', 'Admin\DiscountController@table')->name('discounts');;
        Route::post('coaches', 'Admin\CoachController@table')->name('coaches');
        Route::post('ads', 'Admin\AdsController@table')->name('ads');
        Route::post('editors/ads', 'EditorArea\AdsController@table')->name('editorads');
        Route::post('payments', 'Admin\PaymentController@table')->name('payments');
        Route::post('manuallypayments', 'Admin\PaymentController@tablemanually')->name('manuallypayments');
        Route::post('editors/manuallypayments', 'EditorArea\PaymentController@tablemanually')->name('manuallypayments');
        Route::post('credits', 'Admin\CreditController@table')->name('credits');
        Route::post('editors/credits', 'EditorArea\CreditController@table')->name('credits');
        Route::post('financial', 'Admin\FinancialController@table')->name('financial');
    });
    Route::prefix('check')->name('check.')->group(function () {
        Route::post('reagent', 'Admin\UserController@reagent');
        Route::post('discount', 'Admin\DiscountController@checkCode');
    });
});

$router->group(['prefix' => 'ajx'], function () use ($router) {
    $router->post('users/check-email', 'UserController@checkEmail')->name("ajx.checkEmail");
    $router->post('users', 'UserController@store');
});
/**
 * temporary routes
 */
Route::resource('programs', 'ProgramController')->only(['store' , 'index','show']);
/**
 * dashboards
 */

/* client */
Route::prefix('ClientArea')->name('ClientArea.')->namespace('ClientArea')->middleware('auth')->group(function () {
    // requests
    Route::resource('requests', 'RequestController');
    Route::group(['prefix' => 'requests', 'as' => 'requests.'], function (){
        Route::post('get-requests', 'RequestController@getRequests')->name('getRequests');
    });

    // transactions
    Route::resource('transactions', 'TransactionController');
    Route::group(['prefix' => 'transactions', 'as' => 'transactions.'], function (){
        Route::post('get-transactions', 'TransactionController@getTransactions')->name('getTransactions');
    });


    Route::get('/', 'HomeController@index');

    // profile
    Route::group(['prefix' => 'profile', 'as' => 'profile.'], function (){
        Route::get('/', 'ProfileController@edit')->name('edit');
        Route::put('/', 'ProfileController@update')->name('update');
        Route::get('change-password', 'ProfileController@changePassword')->name('changePassword');
        Route::put('update-password', 'ProfileController@updatePassword')->name('updatePassword');
    });
});

/* coach */
Route::prefix('admin/coach-area')->name('CoachArea.')->namespace('CoachArea')->middleware('coach')->group(function () {
    // requests
    Route::resource('requests', 'RequestController');
    Route::group(['prefix' => 'requests', 'as' => 'requests.'], function (){
        Route::post('getRequests', 'RequestController@getRequests')->name('getRequests');

        Route::post('change-program-status', 'RequestController@changeProgramStatus')->name('changeProgramStatus');
        Route::get('download-request-file/{tracking_code}', 'RequestController@downloadRequestFile')->name('downloadRequestFile');
        /*added by aeini */
        Route::post('updateProgramStatus', 'RequestController@updateProgramStatus')->name('updateProgramStatus');
        //Route::post('store', 'RequestController@store')->name('store');

    });


    // profile
    Route::group(['prefix' => 'profile', 'as' => 'profile.'], function (){
        Route::get('/', 'ProfileController@edit')->name('edit');
        Route::put('update', 'ProfileController@update')->name('update');
        Route::get('change-password', 'ProfileController@changePassword')->name('changePassword');
        Route::put('update-password', 'ProfileController@updatePassword')->name('updatePassword');
    });

});

/* admin */
Route::prefix('admin')->name('admin.')->namespace('Admin')->middleware('auth')->group(function () {
    // categories
    Route::resource('categories','CategoryController');
    Route::group(['prefix' => 'categories', 'as' => 'categories.'], function (){
        Route::post('get-categories', 'CategoryController@getCategories')->name('getCategories');
    });
    // credits
    Route::resource('credits','CreditController');
    Route::group(['prefix' => 'credits', 'as' => 'credits.'], function (){
        Route::post('get-users', 'CreditController@getUsers')->name('getUsers');
    });
    Route::get('coaches/financial/{id}', 'FinancialController@getCoachFinancial');

    Route::get('users/{id}/{field}/{status}', 'UserController@changeStatus');
    Route::get('coach_fields/{id}/{field}/{status}', 'CoachController@changeVisible');

    Route::get('/', 'HomeController@index')->name('index');
    Route::middleware('admin')->group(function (){
        // discounts
        Route::resource('discounts', 'DiscountController');
        Route::resource('financial', 'FinancialController');
        Route::group(['prefix => discounts', 'as' => 'discounts.'], function (){
            Route::post('get-discounts', 'DiscountController@getDiscounts')->name('getDiscounts');
        });

        // users
        Route::resource('users', 'UserController');
        // fakes
        Route::resource('fakes', 'FakeController');
        Route::resource('coaches', 'CoachController');
        // editors
        Route::resource('editors', 'EditorController');
        Route::group(['prefix' => 'editors', 'as' => 'editors.'], function (){
            Route::post('get-editors', 'EditorController@getEditors')->name('getEditors');
        });
        Route::group(['prefix' => 'users', 'as' => 'users.'], function (){
            Route::post('get-users', 'UserController@getUsers')->name('getUsers');
        });
        Route::group(['prefix' => 'fakes', 'as' => 'fakes.'], function (){
            Route::post('get-users', 'FakeController@getUsers')->name('getUsers');
        });
        // payments
        Route::group(['prefix' => 'payments', 'as' => 'payments.'], function () {
            Route::get('manually', 'PaymentController@manually')->name('manually');

        });
        Route::resource('payments', 'PaymentController');

        // purchase
        Route::group(['prefix' => 'purchases', 'as' => 'purchases.'], function (){
            Route::post('get-purchases', 'PurchaseController@getPurchases')->name('getPurchases');
            Route::resource('', 'PurchaseController', [
                'parameters' => ['' => 'id'],
                'except' => 'show'
            ]);
        });

        // profile
        Route::group(['prefix' => 'profile', 'as' => 'profile.'], function (){
            Route::get('edit', 'ProfileController@edit')->name('edit');
            Route::put('update', 'ProfileController@update')->name('update');
            Route::get('change-password', 'ProfileController@changePassword')->name('changePassword');
            Route::put('update-password', 'ProfileController@updatePassword')->name('updatePassword');
        });

        //
        Route::resource('QA', 'QAController', [
            'except' => ['show']
        ]);

        Route::resource('questions', 'QuestionController');

        // plans
        Route::group(['prefix' => 'plans', 'as' => 'plans.'], function (){
            Route::post('get-plans', 'PlanController@getPlans')->name('getPlans');
            Route::resource('', 'PlanController', [
                'parameters' => ['' => 'id'],
                'except' => 'show'
            ]);
        });
        // workouts
        Route::group(['prefix' => 'workouts', 'as' => 'workouts.'], function (){
            Route::post('get-workouts', 'WorkoutController@getWorkouts')->name('getWorkouts');
            Route::delete('delete-addable/{id}', 'WorkoutController@deleteAddable')->name('deleteAddable');
        });
        Route::resource('workouts','WorkoutController');
        // equipments
        Route::group(['prefix' => 'equipments', 'as' => 'equipments.'], function (){
            Route::post('get-equipments', 'EquipmentController@getEquipments')->name('getEquipments');
            Route::delete('delete-addable/{id}', 'EquipmentController@deleteAddable')->name('deleteAddable');
        });
        Route::resource('equipments','EquipmentController');

        // supplements
        Route::group(['prefix' => 'supplements', 'as' => 'supplements.'], function (){
            Route::post('get-supplements', 'SupplementController@getSupplements')->name('getSupplements');
            Route::delete('delete-addable/{id}', 'SupplementController@deleteAddable')->name('deleteAddable');
        });
        Route::resource('supplements','SupplementController');

        // nutrients
        Route::group(['prefix' => 'nutrients', 'as' => 'nutrients.'], function (){
            Route::post('get-nutrients', 'NutrientController@getNutrients')->name('getNutrients');
            Route::delete('delete-addable/{id}', 'NutrientController@deleteAddable')->name('deleteAddable');
        });
        Route::resource('nutrients','NutrientController');

        // gyms
        Route::group(['prefix' => 'gyms', 'as' => 'gyms.'], function (){
            Route::post('get-gyms', 'GymController@getGyms')->name('getGyms');
            Route::delete('delete-addable/{id}', 'GymController@deleteAddable')->name('deleteAddable');
        });
        Route::resource('gyms','GymController');
        // podcasts
        Route::group(['prefix' => 'podcasts', 'as' => 'podcasts.'], function (){
            Route::post('get-podcasts', 'PodcastController@getPodcasts')->name('getPodcasts');
            Route::resource('', 'PodcastController', [
                'parameters' => ['' => 'id'],
                'except' => 'show'
            ]);
        });
        // ‌Blogs
        Route::group(['prefix' => 'blogs', 'as' => 'blogs.'], function (){
            Route::post('get-blogs', 'BlogController@getBlogs')->name('getBlogs');
            Route::resource('', 'BlogController', [
                'parameters' => ['' => 'id'],
                'except' => 'show'
            ]);
        });

        // plans-specified
        Route::group(['prefix' => 'plans-specified', 'as' => 'plansSpecified.'], function (){
            Route::post('get-plans-specified', 'PlanPriceController@getPlansSpecified')->name('getPlansSpecified');
            Route::resource('', 'PlanPriceController', [
                'parameters' => ['' => 'id'],
                'except' => 'show'
            ]);
        });

        Route::resource('options', 'OptionController', [
            'except' => ['show']
        ]);
        Route::group(['prefix' => 'options', 'as' => 'options.'], function () {

            Route::post('FilterFemalAccessToCoach/{id}', 'OptionController@FilterFemalAccessToCoach')->name('FilterFemalAccessToCoach');
        });
        // requests
        Route::resource('requests', 'RequestController', ['only' => ['show', 'index','destroy']]);
        Route::group(['prefix' => 'requests', 'as' => 'requests.'], function (){
            Route::post('get-requests', 'RequestController@getRequests')->name('getRequests');
            Route::post('change-payment-type', 'RequestController@changeProgramStatus')->name('changeProgramStatus');
            Route::get('download-request-file/{tracking_code}', 'RequestController@downloadRequestFile')->name('downloadRequestFile');
            /*added by aeini */
            Route::post('updateProgramStatus', 'RequestController@updateProgramStatus')->name('updateProgramStatus');

        });

        //Ads
        Route::resource('ads', 'AdsController');
        Route::get('ads/{id}/{field}/{status}', 'AdsController@changeStatus');
        /*image manger*/

        Route::resource('images', "ImageController");

        // wizard form messages
        Route::group(['prefix' => 'wizard-form-messages', 'as' => 'WizardFormMessages.'], function (){
            Route::post('getMessages', 'WizardFormMessageController@getMessages')->name('getMessages');
            Route::resource('', 'WizardFormMessageController', [
                'parameters' => ['' => 'id'],
                'except' => 'show'
            ]);
        });

    });

    // Image Manager. added by esmaeil soumari
    Route::group(['prefix' => 'image-manager', 'as' => 'imageManager.'], function (){
        Route::get('/', 'ImageManagerController@index')->name('index');
        Route::get('/delete/{image}', 'ImageManagerController@destroy')->name('destroy');
        Route::get('/delete-selected-images/{images}', 'ImageManagerController@deleteSelectedImages')->name('deleteSelectedImages');
        Route::get('/delete-images/all', 'ImageManagerController@deleteAllImages')->name('deleteAllImages');
    });
});


/* Editor Area */
Route::prefix('admin/editor-area')->name('EditorArea.')->namespace('EditorArea')->middleware('editor')->group(function () {
    // discounts
    Route::resource('discounts', 'DiscountController');
    Route::resource('financial', 'FinancialController');
    Route::group(['prefix => discounts', 'as' => 'discounts.'], function (){
        Route::post('get-discounts', 'DiscountController@getDiscounts')->name('getDiscounts');
    });

    // profile
    Route::group(['prefix' => 'profile', 'as' => 'profile.'], function (){
        Route::get('edit', 'ProfileController@edit')->name('edit');
        Route::put('update', 'ProfileController@update')->name('update');
        Route::get('change-password', 'ProfileController@changePassword')->name('changePassword');
        Route::put('update-password', 'ProfileController@updatePassword')->name('updatePassword');
    });
    // credits
    Route::resource('credits','CreditController');
    Route::group(['prefix' => 'credits', 'as' => 'credits.'], function (){
        Route::post('get-users', 'CreditController@getUsers')->name('getUsers');
    });
    // categories
    Route::resource('categories','CategoryController');
    Route::group(['prefix' => 'categories', 'as' => 'categories.'], function (){
        Route::post('get-categories', 'CategoryController@getCategories')->name('getCategories');
    });
    // requests
    Route::resource('requests', 'RequestController', ['only' => ['show', 'index','destroy']]);
    Route::group(['prefix' => 'requests', 'as' => 'requests.'], function (){
        Route::post('get-requests', 'RequestController@getRequests')->name('getRequests');
        Route::post('change-payment-type', 'RequestController@changeProgramStatus')->name('changeProgramStatus');
        Route::get('download-request-file/{tracking_code}', 'RequestController@downloadRequestFile')->name('downloadRequestFile');
        /*added by aeini */
        Route::post('updateProgramStatus', 'RequestController@updateProgramStatus')->name('updateProgramStatus');

    });
    //Ads
    Route::resource('ads', 'AdsController');
    Route::get('ads/{id}/{field}/{status}', 'AdsController@changeStatus');
    // payments
    Route::group(['prefix' => 'payments', 'as' => 'payments.'], function () {
        Route::get('manually', 'PaymentController@manually')->name('manually');

    });
    Route::resource('payments', 'PaymentController');
    // users
    Route::resource('users', 'UserController');
    // Route::get('users/{id}/{field}/{status}', 'UserControllerUserController@changeStatus');
    Route::group(['prefix' => 'users', 'as' => 'users.'], function (){
        Route::post('get-users', 'UserController@getUsers')->name('getUsers');
    });
    // podcasts
    Route::group(['prefix' => 'podcasts', 'as' => 'podcasts.'], function (){
        Route::post('get-podcasts', 'PodcastController@getPodcasts')->name('getPodcasts');
        Route::resource('', 'PodcastController', [
            'parameters' => ['' => 'id'],
            'except' => 'show'
        ]);
    });
    // ‌Blogs
    Route::group(['prefix' => 'blogs', 'as' => 'blogs.'], function (){
        Route::post('get-blogs', 'BlogController@getBlogs')->name('getBlogs');
        Route::resource('', 'BlogController', [
            'parameters' => ['' => 'id'],
            'except' => 'show'
        ]);
    });

    // workouts
    Route::group(['prefix' => 'workouts', 'as' => 'workouts.'], function (){
        Route::post('get-workouts', 'WorkoutController@getWorkouts')->name('getWorkouts');
        Route::delete('delete-addable/{id}', 'WorkoutController@deleteAddable')->name('deleteAddable');
    });
    Route::resource('workouts','WorkoutController');
    // equipments
    Route::group(['prefix' => 'equipments', 'as' => 'equipments.'], function (){
        Route::post('get-equipments', 'EquipmentController@getEquipments')->name('getEquipments');
        Route::delete('delete-addable/{id}', 'EquipmentController@deleteAddable')->name('deleteAddable');
    });
    Route::resource('equipments','EquipmentController');

    // supplements
    Route::group(['prefix' => 'supplements', 'as' => 'supplements.'], function (){
        Route::post('get-supplements', 'SupplementController@getSupplements')->name('getSupplements');
        Route::delete('delete-addable/{id}', 'SupplementController@deleteAddable')->name('deleteAddable');
    });
    Route::resource('supplements','SupplementController');

    // nutrients
    Route::group(['prefix' => 'nutrients', 'as' => 'nutrients.'], function (){
        Route::post('get-nutrients', 'NutrientController@getNutrients')->name('getNutrients');
        Route::delete('delete-addable/{id}', 'NutrientController@deleteAddable')->name('deleteAddable');
    });
    Route::resource('nutrients','NutrientController');

    // gyms
    Route::group(['prefix' => 'gyms', 'as' => 'gyms.'], function (){
        Route::post('get-gyms', 'GymController@getGyms')->name('getGyms');
        Route::delete('delete-addable/{id}', 'GymController@deleteAddable')->name('deleteAddable');
    });
    Route::resource('gyms','GymController');
});
/* end Editor Area */



/**
 * qr
 */
/*Route::get('/{qr}', function ($qr) {
    $endpoints = [
        '1' => 'show',
        '2' => 'show_equ',
        '3' => 'show_nut',
        '4' => 'show_supp',
    ];
    preg_match('/([0-9])([0-9]*)/', $qr, $matches);
    $url = 'http://morabiman.com/fbd/index.php/frontend/' . $endpoints[$matches[1]] . '/' . $matches[2];

    return redirect($url);

})->where('qr', '[0-9]+');*/


Route::get('/', 'RequestController@index')->name('index');
Route::get('/index', 'RequestController@create')->name('create-request');
Auth::routes();
Route::post('/store', 'RequestController@store')->middleware("auth")->name("store");
Route::get('/composition', 'RequestController@composition')->middleware("auth")->name("composition");
Route::post('/pxml', 'RequestController@pxml')->name("pxml");
$router->put('profile', 'UserController@update');
$router->resource('reset-password', 'ResetPasswordController', ['only' => ['index', 'update'],])->middleware('auth');
Route::get('/home', 'RequestController@index')->name('home');
$router->post('users/getProvinces', 'UserController@getProvinces')->name("ajx.getProvinces");

/*
clear cache from url without terminal */

//Clear Cache facade value:
Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('cache:clear');
    return '<h1>Cache facade value cleared</h1>';
});

//Clear Config cache:
Route::get('/config-clear', function () {
    $exitCode = Artisan::call('config:cache');
    return '<h1>Clear Config cleared</h1>';
});


Route::get('/migrate', function () {
    $exitCode = Artisan::call('migrate');
    echo '<h1>migrate is successful</h1>';

    echo '<br>';
    echo 'exitCode: ' . $exitCode;
    die;
});


Route::get('/clear-all', function () {
    $exitCode[] = Artisan::call('view:clear');
    $exitCode[] = Artisan::call('cache:clear');
    $exitCode[] = Artisan::call('config:cache');
    $exitCode[] = Artisan::call('route:clear');
    echo '<h1>All Sections cleared</h1>';
    echo '<br>';
    print_r($exitCode);
    die;
});
Route::get('my-captcha', 'HomeController@myCaptcha')->name('myCaptcha');
Route::post('my-captcha', 'HomeController@myCaptchaPost')->name('myCaptcha.post');
Route::get('/refresh_captcha', 'UserController@refreshCaptcha')->name('refresh_captcha');
Route::get('showTrackingCode/{code}', 'RequestController@showTrackingCode')->name('showTrackingCode');

Route::post('changeMessageDisable', 'Admin\CoachController@messageDisabled')->name('changeMessageDisable');
Route::get('qr/show','QrController@index');
Route::get('equipment/{id}/','QrController@show_equ');
Route::get('supplement/{id}/','QrController@show_supp');
Route::get('nutrient/{id}/','QrController@show_nut');
Route::get('workout/{id}/','QrController@show_work');
Route::post('search_cat','QrController@searchCategory');


Route::get('clear', function (){
    //Artisan::call('config:clear');
});
$router->post('sms_configuration', 'RequestController@smsConfiguration');
$router->post('check_user_credit', 'RequestController@checkUserCredit');
Route::get('login/admin','RequestController@loginForm');
$router->post('change-score', 'RequestController@changeScore');
Route::any('/student/tracking','RequestController@tracking');
Route::post('/admin/logout','HomeController@admin_logout');
Route::get('testPay','TestController@getPaymentWhichSuccess');

Route::get('updatePayStatus','TestController@UpdatePaymentWhichSuccessIngateway');
