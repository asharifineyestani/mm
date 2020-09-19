<?php

namespace App\Rules;

use App\Models\Plan;
use App\Models\Price;
use Illuminate\Contracts\Validation\Rule;

class PlanRule implements Rule
{

    public $coachId;


    public function __construct($coachId)
    {

        $this->coachId = $coachId;
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
        foreach ($value as $planId) {
            $exist = Price::where('user_id', $this->coachId)->where('plan_id', $planId)->exists();

            if (!$exist)
                return false;
        }


      return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'مطمین شوید مربی همه ی پلن های ارسالی شما را داراست';
    }
}
