<?php

namespace Helori\PhpSign\Utilities;

use Carbon\Carbon;


class DateParser
{
    /**
     * Convert a date to a Carbon instance
     *
     * @param  mixed $dateInput
     * @param  string $fromTimezone
     * @param  string $toTimezone
     * @return \Carbon\Carbon|null
     */
    public static function parse($dateInput, string $fromTimezone = 'UTC', string $toTimezone = 'UTC')
    {
        $date = null;

        if(!$dateInput){

            $date = null;

        }else if(is_string($dateInput)){

            try{
                $date = Carbon::parse($dateInput, $fromTimezone)->setTimezone($toTimezone);
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
