<?php

namespace App\EX;

use App\WalletActionsLog;

class Logger
{
    /**
     * @var int
     */
    private $id;

    /**
     * Logger constructor.
     */
    public function __construct()
    {

    }

    /**
     * @param array $data
     */
    public function save(array $data)
    {
        $data = json_decode(json_encode($data));

        $log = new WalletActionsLog();
        {
            if (isset($data->action)) {
                $log->action = $data->action;
            }
            if (isset($data->user_id)) {
                $log->user_id = $data->user_id;
            }
            if (isset($data->wallet_id)) {
                $log->wallet_id = $data->wallet_id;
            }
            if (isset($data->balance)) {
                $log->balance = $data->balance;
            }
            if (isset($data->amount)) {
                $log->amount = $data->amount;
            }
            if (isset($data->secondary_user_id)) {
                $log->secondary_user_id = $data->secondary_user_id;
            }
            if (isset($data->secondary_wallet_id)) {
                $log->secondary_wallet_id = $data->secondary_wallet_id;
            }
            if (isset($data->delta)) {
                $log->delta = $data->delta;
            }
            if (isset($data->amount_usd)) {
                $log->amount_usd = $data->amount_usd;
            }
            if (isset($data->extra)) {
                $log->extra = substr(json_encode($data->extra), 0, 1000);
            }
        }
        $log->save();

        $this->id = $log->id;
    }

    /**
     * @param array $data
     */
    public function saveResponse(array $data)
    {
        $data = json_decode(json_encode($data));

        $log = WalletActionsLog::find($this->id);
        {
            if (isset($data->extra)) {
                $log->extra = substr(json_encode($data->extra), 0, 1000);
            }
        }
        $log->save();

        $this->id = null;
    }
}