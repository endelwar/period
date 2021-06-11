<?php

namespace Spatie\Period\Exceptions;

use Exception;
use Spatie\Period\Precision;

class CannotCeilLowerPrecision extends Exception
{
    public static function precisionIsLower(Precision $a, Precision $b): CannotCeilLowerPrecision
    {
        $from = self::unitName($a);
        $to = self::unitName($b);

        return new self("Cannot get the latest $from of a $to.");
    }

    protected static function unitName(Precision $precision)
    {
        switch ($precision->intervalName()) {
            case 'y':
                return 'year';
            case 'm':
                return 'month';
            case 'd':
                return 'day';
            case 'h':
                return 'hour';
            case 'i':
                return 'minute';
            case 's':
                return 'second';
        }
    }
}
