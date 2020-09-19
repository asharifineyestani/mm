<?php
//
//
Route::resource('/received', 'ReceivedController');
Route::resource('/adapted', 'AdaptedController');


Route::get('/getData', 'ReceivedController@getData');


Route::get('/do/{id}', 'AdaptedController@doAdapt');
Route::get('/updateAll', 'AdaptedController@updateAll');
Route::get('/update/{id}', 'AdaptedController@update');
Route::get('/showBeforeUpdate/{id}', 'AdaptedController@showBeforeUpdate');
Route::get('/showOriginal/{id}', 'AdaptedController@showOriginal');



Route::resource('/tests', 'TestController');
