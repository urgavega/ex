<?php

namespace App\Http\Controllers;

use App\Currency;
use App\EX\Logger;
use App\EX\User;
use App\EX\Wallet;
use App\Exceptions\EXErrorException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {

    }

    public function getUser(Request $request)
    {
        $user = new User($request->id);
        $result = $user->getUser();

        return response()->json($result, 200);
    }

    public function createUser(Request $request)
    {
        // Создаем нового юзера
        $user = new User();
        $result = $user->createUser($request->all());

        try {
            // Создаем кошелек для нового юзера
            if (!empty($request->currencyId)) {
                $walletCurrencyId = $request->currencyId;
            } else {
                $walletCurrencyId = Currency::where('name', 'USD')->value('id');
            }

            $logger = new Logger();

            $wallet = new Wallet($user->getId(), null, $logger);
            $wallet->createWallet([
              'currencyId' => $walletCurrencyId,
            ]);
        } catch (\Exception $e) {
            $user->delUser();
            throw new EXErrorException('Error!!! User is not created');
        }


        return response()->json($result, 200);
    }
}