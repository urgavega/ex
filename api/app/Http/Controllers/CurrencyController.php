<?php

namespace App\Http\Controllers;

use App\Currency;
use App\Exceptions\EXErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws EXErrorException
     */
    public function setRates(Request $request)
    {
        $validator = Validator::make($request->all(), [
          'date' => 'required|date_format:Y-m-d',
        ])->validate();


        $rates = $request->all();

        $ratesVerified = [];
        foreach (Currency::all() as $k => $v) {
            if (strtoupper($v->name) === 'USD') {
                continue;
            }

            if (!isset($rates[$v->name])) {
                throw new EXErrorException('No rate for ' . $v->name);
            }

            if (!preg_match('/^\d*(\.\d{1,100})?$/', $rates[$v->name])) {
                throw new EXErrorException('Wrong rate for ' . $v->name);
            }
            $ratesVerified[$v->id] = (string)$rates[$v->name];
        }

        $currency = new \App\EX\Currency();
        $result = $currency->setRates($request->date, $ratesVerified);

        return response()->json($result, 200);
    }



}