<?php

namespace App\EX;

use DB;
use Illuminate\Support\Facades\Validator;

class Reporter
{
    /**
     * @var int
     */
    protected $limit;

    /**
     * Reporter constructor.
     */
    public function __construct()
    {
        // Ограничение кол-ва записей в одном запросе
        // В идеале это нужно выносить в файл настроек
        $this->limit = 5;
    }

    /**
     * @param array $request
     * @param string $coef
     * @return object
     */
    public function getData(array $request, string $coef): object
    {
        $request['page'] = !empty($request['page']) ? $request['page'] : 1;

        $validator = Validator::make($request, [
          'userId' => 'required|numeric|gt:0',
          'from' => 'date|date_format:Y-m-d',
          'to' => 'date|after_or_equal:from|date_format:Y-m-d',
          'page' => 'required|numeric|gte:0',
        ])->validate();

        $from = !empty($request['from']) ? $request['from'] : '2001-01-01';
        $to = !empty($request['to']) ? $request['to'] : date('Y-m-d');

        DB::enableQueryLog();
        // Получаем аггрегированые данные по кошелькам пользователя
        $rowsAgg = DB::select('
                SELECT 
                  wal.wallet_id,
                  w.name as wallet_name,
                  c.name as wallet_currency,
                  w.balance,
                  SUM(wal.amount) as sum,
                  SUM(wal.amount_usd) as sum_usd,
                  COUNT(wal.id) as cnt
                FROM 
                  wallets_actions_log wal
                  LEFT JOIN wallets w ON wal.wallet_id = w.id 
                  JOIN currencies c ON w.currency_id = c.id
                WHERE
                  wal.user_id = :userId
                  AND wal.date >= :from
                  AND wal.date <= :to
                GROUP BY
                  wal.wallet_id,
                  w.name,
                  c.name,
                  w.balance',
          [
            'userId' => (int)$request['userId'],
            'from' => $from . ' 00:00:00',
            'to' => $to . ' 23:59:59',
          ]);
//         dd(DB::getQueryLog());

        // Подготавливаем данные для возврата пользователю в привычном виде
        $cnt = 0;
        $amountSumUsd = 0;
        foreach ($rowsAgg as $k => $v) {
            $cnt += $v->cnt;
            $amountSumUsd += $v->sum_usd;

            // TODO Если останется время, то переделать на bcmath
            $rowsAgg[$k]->balance /= $coef;
            $rowsAgg[$k]->sum /= $coef;
            $rowsAgg[$k]->sum_usd /= $coef;
        }
        $pages = ceil($cnt / $this->limit);
        $request['page'] = $request['page'] <= $pages ? $request['page'] : $pages;

        // DB::enableQueryLog();
        // Подробный отчет об операциях клиента
        $rows = DB::select('
                SELECT 
                  wal.*,
                  w.name as wallet_name,
                  c.name as wallet_currency,
                  us.name as secondary_user_name,
                  ws.name as secondary_wallet_name,
                  cs.name as secondary_wallet_currency
                FROM 
                  wallets_actions_log wal
                  LEFT JOIN wallets w ON wal.wallet_id = w.id 
                  LEFT JOIN currencies c ON w.currency_id = c.id
                  LEFT JOIN wallets ws ON wal.secondary_wallet_id = ws.id 
                  LEFT JOIN currencies cs ON ws.currency_id = cs.id
                  LEFT JOIN users us ON us.id = wal.secondary_user_id
                WHERE
                  wal.user_id = :userId
                  AND wal.date >= :from
                  AND wal.date <= :to
                ORDER BY wal.id DESC
                LIMIT :page, :limit',
          [
            'userId' => $request['userId'],
            'from' => $from . ' 00:00:00',
            'to' => $to . ' 23:59:59',
            'limit' => $this->limit,
            'page' => ($request['page'] - 1) * $this->limit
          ]);
        // dd(DB::getQueryLog());
        // Подготавливаем данные для возврата пользователю в привычном виде
        foreach ($rows as $k => $v) {
            // TODO Если останется время, то переделать на bcmath
            $rows[$k]->balance /= $coef;
            $rows[$k]->amount /= $coef;
            $rows[$k]->amount_usd /= $coef;
            // Это пользователю видеть не надо
            unset ($rows[$k]->delta);
            unset ($rows[$k]->extra);
        }

        $result = [
          'userId' => $request['userId'],
          'page' => $request['page'],
          'pages' => $pages,
          'rowsNum' => $cnt,
          'amountSumUsd' => $amountSumUsd / $coef,
          'agg' => $rowsAgg,
          'rows' => $rows
        ];
        if (!empty($request['from'])) {
            $result['from'] = $request['from'];
        }
        if (!empty($request['to'])) {
            $result['to'] = $request['to'];
        }

        return (object)$result;
    }
}