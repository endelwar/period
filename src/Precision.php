<?php

namespace Spatie\Period;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

class Precision
{
    private const YEAR = 0b100000;
    private const MONTH = 0b110000;
    private const DAY = 0b111000;
    private const HOUR = 0b111100;
    private const MINUTE = 0b111110;
    private const SECOND = 0b111111;
    private int $mask;

    /**
     * @return self[]
     */
    public static function all(): array
    {
        return [
            self::YEAR(),
            self::MONTH(),
            self::DAY(),
            self::HOUR(),
            self::MINUTE(),
            self::SECOND(),
        ];
    }

    private function __construct(int $mask)
    {
        $this->mask = $mask;
    }

    public static function fromString(string $string): self
    {
        preg_match('/([\d]{4})(-[\d]{2})?(-[\d]{2})?(\s[\d]{2})?(:[\d]{2})?(:[\d]{2})?/', $string, $matches);

        switch (count($matches) - 1) {
            case 1:
                return self::YEAR();
            case 2:
                return self::MONTH();
            case 3:
                return self::DAY();
            case 4:
                return self::HOUR();
            case 5:
                return self::MINUTE();
            case 6:
                return self::SECOND();
        }
    }

    public static function YEAR(): self
    {
        return new self(self::YEAR);
    }

    public static function MONTH(): self
    {
        return new self(self::MONTH);
    }

    public static function DAY(): self
    {
        return new self(self::DAY);
    }

    public static function HOUR(): self
    {
        return new self(self::HOUR);
    }

    public static function MINUTE(): self
    {
        return new self(self::MINUTE);
    }

    public static function SECOND(): self
    {
        return new self(self::SECOND);
    }

    public function interval(): DateInterval
    {
        switch ($this->mask) {
            case self::SECOND:
                $interval = 'PT1S';
                break;
            case self::MINUTE:
                $interval = 'PT1M';
                break;
            case self::HOUR:
                $interval = 'PT1H';
                break;
            case self::DAY:
                $interval = 'P1D';
                break;
            case self::MONTH:
                $interval = 'P1M';
                break;
            case self::YEAR:
                $interval = 'P1Y';
                break;
        }

        return new DateInterval($interval);
    }

    public function intervalName(): string
    {
        switch ($this->mask) {
            case self::YEAR:
                return 'y';
            case self::MONTH:
                return 'm';
            case self::DAY:
                return 'd';
            case self::HOUR:
                return 'h';
            case self::MINUTE:
                return 'i';
            case self::SECOND:
                return 's';
        }
    }

    public function roundDate(DateTimeInterface $date): DateTimeImmutable
    {
        [$year, $month, $day, $hour, $minute, $second] = explode(' ', $date->format('Y m d H i s'));

        $month = (self::MONTH & $this->mask) === self::MONTH ? $month : '01';
        $day = (self::DAY & $this->mask) === self::DAY ? $day : '01';
        $hour = (self::HOUR & $this->mask) === self::HOUR ? $hour : '00';
        $minute = (self::MINUTE & $this->mask) === self::MINUTE ? $minute : '00';
        $second = (self::SECOND & $this->mask) === self::SECOND ? $second : '00';

        return DateTimeImmutable::createFromFormat(
            'Y m d H i s',
            implode(' ', [$year, $month, $day, $hour, $minute, $second]),
            $date->getTimezone()
        );
    }

    public function ceilDate(DateTimeInterface $date, Precision $precision): DateTimeImmutable
    {
        [$year, $month, $day, $hour, $minute, $second] = explode(' ', $date->format('Y m d H i s'));

        $month = (self::MONTH & $precision->mask) === self::MONTH ? $month : '12';
        $day = (self::DAY & $precision->mask) === self::DAY ? $day : cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $hour = (self::HOUR & $precision->mask) === self::HOUR ? $hour : '23';
        $minute = (self::MINUTE & $precision->mask) === self::MINUTE ? $minute : '59';
        $second = (self::SECOND & $precision->mask) === self::SECOND ? $second : '59';

        return DateTimeImmutable::createFromFormat(
            'Y m d H i s',
            implode(' ', [$year, $month, $day, $hour, $minute, $second]),
            $date->getTimezone()
        );
    }

    public function equals(Precision ...$others): bool
    {
        foreach ($others as $other) {
            if ($this->mask !== $other->mask) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function increment(DateTimeImmutable $date): DateTimeImmutable
    {
        return $this->roundDate($date->add($this->interval()));
    }

    public function decrement(DateTimeImmutable $date): DateTimeImmutable
    {
        return $this->roundDate($date->sub($this->interval()));
    }

    public function higherThan(Precision $other): bool
    {
        return strlen($this->dateFormat()) > strlen($other->dateFormat());
    }

    public function dateFormat(): string
    {
        switch ($this->mask) {
            case Precision::SECOND:
                return 'Y-m-d H:i:s';
            case Precision::MINUTE:
                return 'Y-m-d H:i';
            case Precision::HOUR:
                return 'Y-m-d H';
            case Precision::DAY:
                return 'Y-m-d';
            case Precision::MONTH:
                return 'Y-m';
            case Precision::YEAR:
                return 'Y';
        }
    }
}
