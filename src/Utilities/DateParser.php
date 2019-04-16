<?php

namespace Helori\PhpSign\Utilities;

use Carbon\Carbon;


class DateParser
{
    /**
     * Convert a date to a Carbon instance
     *
     * @param  mixed $dateInput
     * @return \Carbon\Carbon|null
     */
    public static function parse($dateInput)
    {
        $date = null;

        if(!$dateInput){

            $date = null;

        }else if(is_string($dateInput)){

            try{
                $date = Carbon::parse($dateInput);
            }catch(\Exception $e){
                throw new \Exception("Could not parse date to a Carbon object : ".$dateInput);
            }

        }else if($dateInput instanceof Carbon){

            $date = $dateInput->copy();

        }else{

            throw new \Exception("The date has an unknown format");
        }

        return $date;
    }
}
