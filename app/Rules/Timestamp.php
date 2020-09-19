<?php

namespace App\Rules;

use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Contracts\Validation\Rule;

class Timestamp implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        //

        #@todo TEsting
        return true;

        $value = date('Y-m-d H:i:s', substr($value, 0, -3));


        $today = Carbon::today();


        return ($value > $today);

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'تاریخ معتبر نمی باشد';
    }
}
