<?php

namespace App\Http\Controllers;

use App\EX\Reporter;
use App\EX\Wallet;
use App\Exceptions\EXErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReporterController extends Controller
{
    public function __construct()
    {

    }

    public function getData(Request $request)
    {
        $validator = Validator::make($request->all(), [
          'userId' => 'required|numeric|gt:0',
          'from' => 'date|date_format:Y-m-d',
          'to' => 'date|after_or_equal:from|date_format:Y-m-d',
          'page' => 'numeric|gt:0',
        ])->validate();

        $reporter = new Reporter();
        $wallet = new Wallet($request->userId);
        $coef = $wallet->getCoeff();
        $report = $reporter->getData($request->all(), (string)$coef);

        return response()->json($report, 200);
    }
}