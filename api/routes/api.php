<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Получить данные пользователя
Route::get('user', 'UserController@getUser');
    // api/user?id=1
// Создать пользователя. Если валюта не указана, то для нового юзера, кошелек создается в USD
Route::post('user', 'UserController@createUser');
    // api/user?name=Aleksei&country=Cyprus&city=Limassol&currencyId=4

// Создать кошелек
Route::post('wallet', 'WalletController@createWallet');
    // api/wallet?userId=1&currencyId=2
// Добавить денег в кошелек. В валюте кошелька. Сумма может быть с "копейками"
Route::put('wallet/credit', 'WalletController@applyCredit');
    // api/wallet/credit?userId=18&id=14&amount=100
    // api/wallet/credit?userId=18&id=15&amount=200
    // api/wallet/credit?userId=1&id=16&amount=100
    // api/wallet/credit?userId=1&id=16&amount=50
    // api/wallet/credit?userId=1&id=17&amount=5000000
// Получить данные кошелька
Route::get('wallet', 'WalletController@getWallet');
    // api/wallet?userId=1&id=17
// Перевести деньги из одного кошелька в другой. Сумма может быть с "копейками"
// Если указать isSecondaryCurrency=1, то перевод будет в валюте получателя
Route::put('wallet/transfer', 'WalletController@transferCredit');
    // api/wallet/transfer?userId=1&id=17&amount=1000&secondaryWalletId=14
    // api/wallet/transfer?userId=1&id=17&amount=1000&secondaryWalletId=14&isSecondaryCurrency=1

// Загрузить котировки валют на дату. Должны быть все котировки, которые заведены в системе.
Route::post('rates', 'CurrencyController@setRates');
    // api/rates?date=2019-02-17&RUR=66.2645&EUR=0.88615559&CAD=1.3239&VEF=248487.64

// Получить отчет.
Route::get('report', 'ReporterController@getData');
    // api/report?userId=1&from=2019-02-11&to=2019-02-18&page=1
    // api/report?userId=1&page=2