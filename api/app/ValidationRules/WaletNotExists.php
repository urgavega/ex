<?php

namespace App\ValidationRules;

use App\Wallet;
use Illuminate\Contracts\Validation\Rule;

class WaletNotExists implements Rule
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $w = Wallet::where('id', $this->request['id']);
        if (!empty($this->request['userId'])){
            $w = $w->where('user_id', $this->request['userId']);
        }
        return (boolean)($w->count());
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Wallet not exists';
    }
}