<?php
namespace App\ValidationRules;

use App\User;
use Illuminate\Contracts\Validation\Rule;

class UserNotExists implements Rule
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
      $result = User::where('id', $value)->count();

      return (boolean)$result;
  }

  /**
   * Get the validation error message.
   *
   * @return string
   */
  public function message()
  {
    return 'User not exists';
  }
}