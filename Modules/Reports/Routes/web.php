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

    Route::get('/sales-report', 'ReportsController@salesByUser')->name('reports.sales.summary');
    Route::get('/sales-report/details', 'ReportsController@salesReport')->name('reports.sales.details');
    Route::get('/sales-report/pdf', 'ReportsController@downloadSalesSummaryPdf')->name('reports.sales.summary.pdf');
    Route::get('/sales-report/details/pdf', 'ReportsController@downloadSalesDetailsPdf')->name('reports.sales.details.pdf');

    //Profit Loss Report
    Route::get('/profit-loss-report', 'ReportsController@profitLossReport')
        ->name('profit-loss-report.index');
    //Payments Report
    Route::get('/payments-report', 'ReportsController@paymentsReport')
        ->name('payments-report.index');
    //Sales Report
    // Route::get('/sales-report', 'ReportsController@salesReport')
    //     ->name('sales-report.index');
    //Purchases Report
    Route::get('/purchases-report', 'ReportsController@purchasesReport')
        ->name('purchases-report.index');
    //Sales Return Report
    Route::get('/sales-return-report', 'ReportsController@salesReturnReport')
        ->name('sales-return-report.index');
    //Purchases Return Report
    Route::get('/purchases-return-report', 'ReportsController@purchasesReturnReport')
        ->name('purchases-return-report.index');
});
