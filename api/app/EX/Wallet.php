<?php

namespace App\EX;

use App\Currency;
use App\Exceptions\EXErrorException;
use App\ValidationRules\CurrencyCheck;
use App\ValidationRules\WaletNotExists;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\ValidationRules\UserNotExists;

class Wallet
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var int
     */
    protected $coeff;


    /**
     * Wallet constructor.
     * @param int $userId
     * @param int|null $id
     * @param Logger|null $logger
     */
    public function __construct(int $userId, int $id = null, Logger $logger = null)
    {
        $this->coeff = '100';
        $this->setUserId($userId);
        $this->setId($id);

        $this->logger = $logger;
    }

    /**
     * @return int
     */
    public function getCoeff(): int
    {
        return $this->coeff;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId)
    {
        $data_src = [
          'userId' => $userId,
        ];
        $validator = Validator::make($data_src, [
          'userId' => [
            'required',
            'numeric',
            new UserNotExists($userId)
          ],
        ])->validate();
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $userId
     */
    public function setId(int $id = null)
    {
        if (empty($id)) {
            $this->id = null;
        } else {
            $data_src = [
              'id' => $id,
            ];
            $validator = Validator::make($data_src, [
              'id' => [
                'required',
                'numeric',
                new WaletNotExists([
                  'userId' => $this->getUserId(),
                  'id' => $id,
                ])
              ],
            ])->validate();

            $this->id = $id;
        }

    }

    /**
     * @return \stdClass object
     */
    public function getWallet(): object
    {
        $data_src = [
          'id' => $this->id,
        ];
        $validator = Validator::make($data_src, [
          'id' => 'required|numeric',
        ])->validate();

        $result = \App\Wallet::where('id', $this->id)->first()->toArray();
        return (object)$result;
    }

    /**
     * @param array $request
     * @return \stdClass object
     */
    public function createWallet(array $request): object
    {
        $request['userId'] = $this->getUserId();
        $validator = Validator::make($request, [
          'userId' => 'required|numeric',
          'currencyId' => [
            'required',
            'numeric',
            new CurrencyCheck(!empty($request['currencyId']) ? $request['currencyId'] : 0)
          ],
        ])->validate();
        $walletR = (object)$request;

        // Если у кошелька не задано имя, то задаем ему имя по-умолчанию
        if (!empty($request->name)) {
            $walletR->walletName = $request->name;
        } else {
            $walletCurrencyName = Currency::where('id', $walletR->currencyId)->value('name');
            $walletR->walletName = 'New ' . $walletCurrencyName . ' Wallet';
        }

        // Оборачиваем всё в транзакцию
        DB::transaction(function () use ($walletR) {
            $w = new \App\Wallet;
            $w->user_id = $this->getUserId();
            $w->currency_id = $walletR->currencyId;
            $w->name = $walletR->walletName;
            $w->save();

            $this->logger->save([
              'action' => 'createWallet',
              'user_id' => $this->getUserId(),
              'wallet_id' => $w->id,
            ]);

            $this->setId($w->id);
        });

        $result = $this->getWallet();
        return (object)$result;
    }


    /**
     * @param string $amount
     * @return \stdClass object
     * @throws EXErrorException
     */
    public function applyCredit(string $amount, \App\EX\Currency $currency): object
    {
        $vld = [
          'amount' => $amount,
          'id' => $this->getId(),
          'userId' => $this->getUserId()
        ];
        $validator = Validator::make($vld, [
          'id' => [
            'required',
            'numeric',
            new WaletNotExists([
              'userId' => $this->getUserId(),
              'id' => $this->getId(),
            ])
          ],
          'amount' => 'required|numeric|regex:/^\d*(\.\d{1,4})?$/'
        ])->validate();

        $amount = bcmul($amount, $this->getCoeff());

        // Узнаем ID валюты у обоих кошельков
        $currencyId = $this->getCurrencyIdByWalletId($this->getId());
        $result = $currency->exchangeCurrency($currencyId, 1, $amount);

        // Оборачиваем в транзакцию
        DB::transaction(function () use ($amount, $result) {
            $balance = \App\Wallet::where('id', $this->getId())->value('balance');
            DB::table('wallets')->where('id', $this->getId())->update(['balance' => $balance + $amount]);

            $this->logger->save([
              'action' => 'applyCredit',
              'user_id' => $this->getUserId(),
              'wallet_id' => $this->getId(),
              'balance' => $balance + $amount,
              'amount' => $amount,
              'amount_usd' => $result->amountUsd,
            ]);
        });


        $result = $this->getWallet();
        return (object)$result;
    }

    /**
     * @param array $request
     * @return \stdClass object
     * @throws EXErrorException
     */
    public function transferCredit(array $request, \App\EX\Currency $currency): object
    {
        $request['userId'] = $this->getUserId();
        $request['id'] = $this->getId();
        $validator = Validator::make($request, [
          'id' => [
            'required',
            'numeric',
            new WaletNotExists([
              'userId' => $this->getUserId(),
              'id' => $this->getId(),
            ])
          ],
          'userId' => 'required|numeric',
          'secondaryWalletId' => [
            'required',
            'numeric',
            new WaletNotExists([
              'id' => !empty($request['secondaryWalletId']) ? $request['secondaryWalletId'] : null,
            ])
          ],
          'amount' => 'required|numeric|regex:/^\d*(\.\d{1,4})?$/'
        ])->validate();
        $walletR = (object)$request;

        // Преобразуем к минимальной неделимой единице валюты (например, копейки)
        $walletR->amount = bcmul((string)$walletR->amount, $this->getCoeff());


        // Узнаем ID валюты у обоих кошельков
        $walletR->currencyId = $this->getCurrencyIdByWalletId($this->getId());
        $walletR->secondaryCurrencyId = $this->getCurrencyIdByWalletId($walletR->secondaryWalletId);

        // Узнаем ID пользователя второго кошелька
        $walletR->secondaryUserId = $this->getUserIdByWalletId($walletR->secondaryWalletId);

        // Делаем конвертацию
        $result = $currency->exchangeCurrency($walletR->currencyId, $walletR->secondaryCurrencyId, $walletR->amount,
          !empty($walletR->isSecondaryCurrency));
//        echo "<pre>"; print_r($result); echo"</pre>"; exit;

        // Проверка хватает ли денег на балансе в кошельке
        $balance = \App\Wallet::where('id', $this->getId())->value('balance');
        if ((int)$balance < $result->amount) {
            throw new EXErrorException('Not enough money');
        }

        // Оборачиваем в транзакцию
        // Сохраняем для одного пользователя как приход
        // А для другого как расход
        DB::transaction(function () use ($result, $walletR) {
            $w = \App\Wallet::find($this->getId());
            $w->balance = $w->balance - $result->amount;
            $w->save();

            $this->logger->save([
              'action' => 'transferCredit',
              'user_id' => $this->getUserId(),
              'wallet_id' => $this->getId(),
              'balance' => $w->balance,
              'amount' => '-' . $result->amount,
              'amount_usd' => '-' . $result->amountUsd,
              'secondary_user_id' => $walletR->secondaryUserId,
              'secondary_wallet_id' => $walletR->secondaryWalletId,
              'delta' => !empty($walletR->isSecondaryCurrency) ? $result->delta : null
            ]);


            $w = \App\Wallet::find($walletR->secondaryWalletId);
            $w->balance = $w->balance + $result->secondaryAmount;
            $w->save();

            $this->logger->save([
              'action' => 'transferCredit',
              'user_id' => $walletR->secondaryUserId,
              'wallet_id' => $walletR->secondaryWalletId,
              'balance' => $w->balance,
              'amount' => $result->secondaryAmount,
              'amount_usd' => $result->amountUsd,
              'secondary_user_id' => $this->getUserId(),
              'secondary_wallet_id' => $this->getId(),
              'delta' => empty($walletR->isSecondaryCurrency) ? $result->delta : null
            ]);
        });

        return (object)[
          'action' => 'transferCredit',
          'userId' => $this->getUserId(),
          'id' => $this->getId(),
          'amount' => $result->amount,
          'amountUsd' => $result->amountUsd,
          'secondaryUserId' => $walletR->secondaryUserId,
          'secondaryWalletId' => $walletR->secondaryWalletId,
        ];
    }

    /**
     * @param int $id
     * @return int
     * @throws EXErrorException
     */
    private function getUserIdByWalletId(int $id): int
    {
        $userId = \App\Wallet::where('id', $id)->value('user_id');
        if (!$userId) {
            throw new EXErrorException('Not found user for wallet. Call to our support service.');
        }
        return $userId;
    }

    /**
     * @param int $id
     * @return int
     * @throws EXErrorException
     */
    private function getCurrencyIdByWalletId(int $id): int
    {
        $currencyId = \App\Wallet::where('id', $id)->value('currency_id');
        if (!$currencyId) {
            throw new EXErrorException('Not found currency for wallet. Call to our support service.');
        }
        return $currencyId;
    }
}