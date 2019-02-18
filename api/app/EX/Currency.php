<?php

namespace App\EX;

use App\CurrencyRate;
use App\Exceptions\EXErrorException;
use App\ValidationRules\CurrencyCheck;
use Illuminate\Support\Facades\Validator;

class Currency
{
    protected $coeff;

    /**
     * Currency constructor.
     */
    public function __construct()
    {
        // Параметр, которые переводит котировки в целое число.
        // Соответственно котировки должны иметь не больше 6 знаков после запятой
        // Если бывают котировки более точные (я не нашел), то нужно увеличить этот параметр.
        // В идеале это нужно выносить в файл настроек
        $this->coeff = '1000000';
    }

    /**
     * @param string $date
     * @param array $rates
     * @return array
     */
    public function setRates(string $date, array $rates): array
    {
        $vld = [
          'date' => $date
        ];
        $validator = Validator::make($vld, [
          'date' => 'required|date_format:Y-m-d',
        ])->validate();

        // Очищаем предыдущие котировки на эту дату
        CurrencyRate::where('date', $date)->delete();

        // Сохраняем котировки приведенными к целому виду с учетом коэффициета
        foreach ($rates as $k => $v) {
            $cr = new CurrencyRate();
            $cr->date = $date;
            $cr->currency_id = $k;
            $cr->rate = bcmul((string)$v, $this->getCoeff());
            $cr->save();
        }

        // Возвращаем котировки оператору в обычном виде.
        $result = $this->getRates($date);
        foreach ($result as $k => $v) {
            $result[$k]->rate = rtrim(bcdiv((string)$v->rate, $this->getCoeff(), 10), 0);
        }
        return $result;
    }

    /**
     * @param string $date
     * @return array
     */
    public function getRates(string $date): array
    {
        $result = CurrencyRate::where('date', $date)->get()->toArray();
        $result = json_decode(json_encode($result));
        return $result;
    }


    /**
     * @param int $currencyId
     * @param int $secondaryCurrencyId
     * @param string $amount
     * @param bool $isSecondaryCurrency
     * @return \stdClass object
     * @throws EXErrorException
     */
    public function exchangeCurrency(int $currencyId, int $secondaryCurrencyId, string $amount, bool $isSecondaryCurrency = false): object
    {
        $vld = [
          'currencyId' => $currencyId,
          'secondaryCurrencyId' => $secondaryCurrencyId,
          'amount' => $amount,
        ];
        $validator = Validator::make($vld, [
          'currencyId' => [
            'required',
            'numeric',
            new CurrencyCheck($currencyId)
          ],
          'secondaryCurrencyId' => [
            'required',
            'numeric',
            new CurrencyCheck($secondaryCurrencyId)
          ],
          'amount' => 'required|numeric|regex:/^\d*(\.\d{1,4})?$/'
        ])->validate();

        // Получаем текущую котировку для отправителя и получателя (secondary)
        $rate = $this->getRate(date('Y-m-d'), $currencyId);
        $secondaryRate = $this->getRate(date('Y-m-d'), $secondaryCurrencyId);

        $amountNew = $amount;
        // Конвертация из одной валюты в другую через USD
        if ($isSecondaryCurrency) {
            // Расчет в валюте получателя
            $secondaryAmount = $amountNew;

            $f1 = gmp_mul($secondaryAmount, $rate);
            $qr = gmp_div_qr($f1, $secondaryRate);
            $amountNew = (string)$qr[0];
        } else {
            // Расчет в валюте отправителя

            $f1 = gmp_mul($amountNew, $secondaryRate);
            $qr = gmp_div_qr($f1, $rate);
            $secondaryAmount = (string)$qr[0];
        }
        $delta = (string)$qr[1];

        // Если валюта получателя USD, то преобразрвание не нужно
        if ((int)$secondaryCurrencyId === 1) {
            $amountUsd = $secondaryAmount;
        } else {
            $amountUsd = gmp_div((int)$amountNew . substr($this->getCoeff(), 1), $rate);
        }

        return (object)[
          'currencyId' => $currencyId,
          'rate' => $rate,
          'amount' => $amountNew,
          'secondaryCurrencyId' => $secondaryCurrencyId,
          'secondaryRate' => $secondaryRate,
          'secondaryAmount' => $secondaryAmount,
          'amountUsd' => (string)$amountUsd,
          'delta' => substr($delta, 0, 3) // Величина округления
        ];
    }

    /**
     * @param string $date
     * @param int $currencyId
     * @return string
     * @throws EXErrorException
     */
    public function getRate(string $date, int $currencyId): string
    {
        $vld = [
          'date' => $date,
          'currencyId' => $currencyId,
        ];
        $validator = Validator::make($vld, [
          'date' => 'required|date_format:Y-m-d',
          'currencyId' => 'required|numeric',
        ])->validate();

        if ($currencyId === 1) {
            return $this->getCoeff();
        }

        $rate = CurrencyRate::where('date', $date)->where('currency_id', $currencyId)->value('rate');
        if (!$rate) {
            throw new EXErrorException('Rate not found');
        }

        return $rate;
    }

    /**
     * @return int
     */
    public function getCoeff(): int
    {
        return $this->coeff;
    }
}