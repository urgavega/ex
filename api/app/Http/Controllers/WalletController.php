<?php

namespace App\Http\Controllers;

use App\EX\Logger;
use App\EX\Wallet;
use App\Exceptions\EXErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function __construct()
    {

    }

    public function getWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
          'userId' => 'required|numeric',
          'id' => 'required|numeric',
        ])->validate();

        $wallet = new Wallet($request->userId, $request->id);
        $result = $wallet->getWallet();
        $result->balance = bcdiv((string)$result->balance, $wallet->getCoeff(), 10) * 1;

        return response()->json($result, 200);
    }

    public function createWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
          'userId' => 'required|numeric',
        ])->validate();
        $wallet = new Wallet((int)$request->userId, null, new Logger());
        $result = $wallet->createWallet($request->all());
        $result->balance = bcdiv((string)$result->balance, $wallet->getCoeff(), 10) * 1;

        return response()->json($result, 200);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws EXErrorException
     */
    public function applyCredit(Request $request)
    {
        $validator = Validator::make($request->all(), [
          'id' => 'required|numeric',
          'userId' => 'required|numeric',
          'amount' => 'required|numeric|regex:/^\d*(\.\d{1,4})?$/'
        ])->validate();

        $wallet = new Wallet((int)$request->userId, (int)$request->id, new Logger());

        $currency = new \App\EX\Currency();
        $result = $wallet->applyCredit($request->amount, $currency);
        $result->balance = bcdiv((string)$result->balance, $wallet->getCoeff(), 10) * 1;

        return response()->json($result, 200);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws EXErrorException
     */
    public function transferCredit(Request $request)
    {
        $validator = Validator::make($request->all(), [
          'id' => 'required|numeric',
          'userId' => 'required|numeric',
          'secondaryWalletId' => 'required|numeric',
          'amount' => 'required|numeric|regex:/^\d*(\.\d{1,4})?$/'
        ])->validate();

        $user = new Wallet((int)$request->userId, (int)$request->id, new Logger());

        $currency = new \App\EX\Currency();
        $result = $user->transferCredit($request->all(), $currency);

        return response()->json($result, 200);
    }
}