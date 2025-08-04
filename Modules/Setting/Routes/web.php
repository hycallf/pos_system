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

Route::group(['middleware' => 'auth'], function () {

    //Mail Settings
    Route::patch('/settings/smtp', 'SettingController@updateSmtp')->name('settings.smtp.update');
    Route::patch('/settings/midtrans', 'SettingController@updateMidtrans')->name('settings.midtrans.update');
    //General Settings
    Route::get('/settings', 'SettingController@index')->name('settings.index');
    Route::patch('/settings', 'SettingController@update')->name('settings.update');
    Route::post('/settings/update-logo', 'SettingController@updateLogo')->name('settings.update.logo');
    // Units
    Route::resource('units', 'UnitsController')->except('show');
});
