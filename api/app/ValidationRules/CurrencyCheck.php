<?php
namespace App\ValidationRules;

use App\Currency;
use Illuminate\Contracts\Validation\Rule;

class CurrencyCheck implements Rule
{
  protected $request;

  public function __construct($request)
  {
    $this->request = $request;
  }

  /**
   * Determine if the validation rule passes.
   *
   * @param  string  $attribute
   * @param  mixed  $value
   * @return bool
   */
  public function passes($attribute, $value)
  {
      $value = empty($this->request) ? $value : $this->request;

      $result = Currency::where('id', $value)->count();

      return (boolean)$result;
  }

  /**
   * Get the validation error message.
   *
   * @return string
   */
  public function message()
  {
    return 'Currency not exists';
  }
}