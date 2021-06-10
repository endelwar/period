<?php

namespace Spatie\Period;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Spatie\Period\Exceptions\InvalidDate;

class PeriodFactory
{
    public static function fromString(string $periodClass, string $string): Period
    {
        preg_match('/(\[|\()([\d\-\s\:]+)[,]+([\d\-\s\:]+)(\]|\))/', $string, $matches);

        [1 => $startBoundary, 2 => $startDate, 3 => $endDate, 4 => $endBoundary] = $matches;

        $boundaries = Boundaries::fromString($startBoundary, $endBoundary);

        $startDate = trim($startDate);

        $endDate = trim($endDate);

        $precision = Precision::fromString($startDate);

        $start = self::resolveDate($startDate, $precision->dateFormat());

        $end = self::resolveDate($endDate, $precision->dateFormat());

        return new $periodClass(
             $start,
             $end,
             $precision,
             $boundaries,
        );
    }

    /**
     * @param string|\DateTimeInterface $start
     * @param string|\DateTimeInterface $end
     */
    public static function make(
        string $periodClass,
        $start,
        $end,
        ?Precision $precision = null,
        ?Boundaries $boundaries = null,
        ?string $format = null
    ): Period {
        $boundaries ??= Boundaries::EXCLUDE_NONE();
        $precision ??= Precision::DAY();
        $start = $precision->roundDate(self::resolveDate($start, $format));
        $end = $precision->roundDate(self::resolveDate($end, $format));

        /** @var \Spatie\Period\Period $period */
        $period = new $periodClass(
             $start,
             $end,
             $precision,
             $boundaries,
        );

        return $period;
    }

    public static function makeWithBoundaries(string $periodClass, DateTimeImmutable $includedStart, DateTimeImmutable $includedEnd, Precision $precision, Boundaries $boundaries): Period
    {
        $includedStart = $precision->roundDate(self::resolveDate($includedStart));
        $includedEnd = $precision->roundDate(self::resolveDate($includedEnd));
        /** @var \Spatie\Period\Period $period */
        $period = new $periodClass(
             $boundaries->realStart($includedStart, $precision),
             $boundaries->realEnd($includedEnd, $precision),
             $precision,
             $boundaries,
        );

        return $period;
    }

    /**
     * @param \DateTimeInterface|string $date
     */
    protected static function resolveDate(
        $date,
        ?string $format = null
    ): DateTimeImmutable {
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($date);
        }

        if (! is_string($date)) {
            throw InvalidDate::forFormat($date, $format);
        }

        $format = static::resolveFormat($date, $format);

        $dateTime = DateTimeImmutable::createFromFormat($format, $date);

        if ($dateTime === false) {
            throw InvalidDate::forFormat($date, $format);
        }

        if (strpos($format, ' ') === false) {
            $dateTime = $dateTime->setTime(0, 0, 0);
        }

        return $dateTime;
    }

    protected static function resolveFormat(
        string $date,
        ?string $format
    ): string {
        if ($format !== null) {
            return $format;
        }

        if (strpos($format, ' ') === false && strpos($date, ' ') !== false) {
            return 'Y-m-d H:i:s';
        }

        return 'Y-m-d';
    }
}
