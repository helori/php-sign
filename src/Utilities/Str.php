<?php

namespace Helori\PhpSign\Utilities;


class Str
{
    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    public static function ucfirst($string)
    {
        return self::upper(static::substr($string, 0, 1)).self::substr($string, 1);
    }

    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }
}
