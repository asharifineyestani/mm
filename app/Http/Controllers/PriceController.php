<?php

namespace App\Http\Controllers;

use App\Models\PlanItem;
use App\Models\Price;

class PriceController extends Controller
{
    private static $totalPrice = 0;

    public static function total(PlanItem $plan)
    {
        foreach ($plan->getPlanIds() as $planId) {
            $price = Price::where('plan_id', $planId)->where('user_id', $plan->getCoachId())->first();
            Self::$totalPrice += $price->price;
        }

        return Self::$totalPrice;
    }
}
