<?php
/**
 * Created by PhpStorm.
 * User: omid
 * Date: 2/14/19
 * Time: 10:16 AM
 */

namespace App\Helpers;


use Illuminate\Support\Facades\Auth;

class BodyAnalyzer
{
    /*computing BMI bodies (Body Mass Index)*/
    static function BMI($weight, $height)
    {
        $height = $height / 100;
        $height = pow($height, 2);
        $BMI = round(($weight / $height), 2);
        switch ($BMI):
            case $BMI <= 16.5:
                $result = __('mm.bodyAnalyzer.BMI.very_thin');
                break;
            case $BMI > 16.5 && $BMI <= 18.5:
                $result = __('mm.bodyAnalyzer.BMI.thin');
                break;
            case $BMI > 18.5 && $BMI <= 20:
                $result = __('mm.bodyAnalyzer.BMI.normal');
                break;
            case $BMI > 25 && $BMI <= 30:
                $result = __('mm.bodyAnalyzer.BMI.fat');
                break;
            case $BMI > 30 && $BMI <= 35:
                $result = __('mm.bodyAnalyzer.BMI.fat_class_1');
                break;
            case $BMI > 35 && $BMI <= 40:
                $result = __('mm.bodyAnalyzer.BMI.fat_class_2');
                break;
            case $BMI > 40:
                $result = __('mm.bodyAnalyzer.BMI.fat_class_3');
                break;
            default:
                $result = null;
        endswitch;
        return [
            'result' => $result,
            'BMI' => $BMI
        ];
    }

    /*determine BF% of bodies (Body Fat Percent)*/
    static function BF($data)
    {
        if (strtoupper($data['gender']) == 'MALE') {
            /*change kilograms to pound*/
            $lb = 2.20462;
            $weight = $data['weight'] * $lb;

            /*change centimeter to inch*/
            $inch = 0.393701;
            $waist = $data['waist'] * $inch;
            $LeanBodyWeight = (94.42 + (1.082 * $weight)) - (4.15 * $waist);
            $BF = round(((($weight - $LeanBodyWeight) * 100) / $weight), 0);

            switch ($BF):
                case $BF <= 6:
                    $result = 'Necessary';
                    break;
                case $BF <= 14:
                    $result = 'Athletic';
                    break;
                case $BF <= 18:
                    $result = 'Fitness';
                    break;
                case $BF <= 25:
                    $result = 'Acceptable';
                    break;
                default:
                    $result = 'FAT';
                    break;
            endswitch;


        } elseif (strtoupper($data['gender']) == 'FEMALE') {
            /*get body BMI*/
            $BMI = self::BMI($data['weight'], $data['height']);
            $BF = round((1.20 * floatval($BMI['BMI'])) + (0.23 * $data['age']) - 5.4, 0);
            switch ($BF):
                case $BF <= 14:
                    $result = 'Necessary';
                    break;
                case  $BF <= 21:
                    $result = 'Athletic';
                    break;
                case $BF <= 25:
                    $result = 'Fitness';
                    break;
                case $BF <= 32:
                    $result = 'Acceptable';
                    break;
                default:
                    $result = 'FAT';
                    break;
            endswitch;
        } else {
            return __('mm.error.unknown', ['name' => __('mm.public.gender')]);
        }
        return $result . ' : ' . $BF . '%';
    }

    /*determine WHR bodies (Waist / hip ratio)*/
    static function WHR($waist, $hip, $gender)
    {
         $whr_num = 0;
        /*whr formula*/
        $whr_num = round(($waist / $hip) * 100) / 100;
        if ($gender === 'MALE') {
            switch ($whr_num):
                case $whr_num <= 0.85:
                    $returnData = __('mm.bodyAnalyzer.veryGood');
                    break;
                case $whr_num > 0.85 && $whr_num <= 0.90:
                    $returnData = __('mm.bodyAnalyzer.good');
                    break;
                case $whr_num > 0.90 && $whr_num <= 0.95:
                    $returnData = __('mm.bodyAnalyzer.normal');
                    break;
                case $whr_num > 0.95 && $whr_num <= 1:
                    $returnData = __('mm.bodyAnalyzer.up');
                    break;
                case $whr_num > 1:
                    $returnData = 'risk';
                    break;
            endswitch;
        }
        else if ($gender === 'FEMALE'){
            switch ($whr_num):
                case $whr_num <= 0.75:
                    $returnData = __('mm.bodyAnalyzer.veryGood');
                    break;
                case $whr_num > 0.75 && $whr_num <= 0.80:
                    $returnData = __('mm.bodyAnalyzer.good');
                    break;
                case $whr_num > 0.80 && $whr_num <= 0.85:
                    $returnData = __('mm.bodyAnalyzer.normal');
                    break;
                case $whr_num > 0.85 && $whr_num <= 0.90:
                    $returnData = __('mm.bodyAnalyzer.up');
                    break;
                case $whr_num > 0.90 :
                    $returnData = __('mm.bodyAnalyzer.risk');
                    break;
            endswitch;
        }

        else {
            return __('mm.error.unknown', ['name' => __('mm.public.gender')]);
        }
        return $returnData . ' : ' . $whr_num;
    }

    /*determine Body Frame*/
    static function BFrame($gender,$height,$wrist){
        $BFrame = round(($height/$wrist),1);

        if (strtoupper($gender) == "MALE") {
            if ($BFrame >= 10.4)
                return "ریز";
            else
                if ($BFrame > 9.5 && $BFrame < 10.4)
                    return "متوسط" ;
                else
                    if ($BFrame <= 9.5)
                        return "درشت" ;
        } else
            if (strtoupper($gender) == "FEMALE") {
                if ($BFrame >= 11)
                    return "ریز";
                else
                    if ($BFrame > 10.1 && $BFrame < 11)
                        return "متوسط" ;
                    else
                        if ($BFrame <= 10.1)
                            return "درشت" ;
            }
    }

    static function Composition($weight,$height,$waist)
    {

            $BMI = BodyAnalyzer::BMI($weight,$height)['result'];

            $BF = BodyAnalyzer::BF([
                'gender'=>Auth::user()->gender,
                'weight' => $weight,
                'height' => $height,
                'age' => date_diff(date_create(Auth::user()->birth_day), date_create('now'))->y, //computing age from birthday
                'waist' => $waist,
            ]);
           return array($BMI,$BF);

            if($BMI != "" && $BF != "")
            {

            }

    }
}


