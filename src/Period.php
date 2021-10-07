<?php

namespace Spatie\Period;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use IteratorAggregate;
use Spatie\Period\Exceptions\CannotComparePeriods;
use Spatie\Period\Exceptions\InvalidPeriod;
use Spatie\Period\PeriodTraits\PeriodComparisons;
use Spatie\Period\PeriodTraits\PeriodGetters;
use Spatie\Period\PeriodTraits\PeriodOperations;

class Period implements IteratorAggregate
{
    use PeriodGetters;
    use PeriodComparisons;
    use PeriodOperations;

    protected PeriodDuration $duration;

    protected DateTimeImmutable $includedStart;

    protected DateTimeImmutable $includedEnd;

    protected DateInterval $interval;
    protected DateTimeImmutable $start;
    protected DateTimeImmutable $end;
    protected Precision $precision;
    protected Boundaries $boundaries;

    public function __construct(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        Precision $precision,
        Boundaries $boundaries
    ) {
        $this->start = $start;
        $this->end = $end;
        $this->precision = $precision;
        $this->boundaries = $boundaries;
        if ($start > $end) {
            throw InvalidPeriod::endBeforeStart($start, $end);
        }

        $this->interval = $this->precision->interval();
        $this->includedStart = $boundaries->startIncluded() ? $start : $start->add($this->interval);
        $this->includedEnd = $boundaries->endIncluded() ? $end : $end->sub($this->interval);
        $this->duration = new PeriodDuration($this);
    }

    /**
     * @param \DateTimeInterface|string $start
     * @param \DateTimeInterface|string $end
     * @return $this
     */
    public static function make(
        $start,
        $end,
        ?Precision $precision = null,
        ?Boundaries $boundaries = null,
        ?string $format = null
    ) {
        return PeriodFactory::make(static::class, $start, $end, $precision, $boundaries, $format);
    }

    /**
     * @return $this
     */
    public static function fromString(string $string)
    {
        return PeriodFactory::fromString(static::class, $string);
    }

    public function getIterator(): DatePeriod
    {
        return new DatePeriod(
            $this->includedStart(),
            $this->interval,
            // We need to add 1 second (the smallest unit available within this package) to ensure entries are counted correctly
            $this->includedEnd()->add(new DateInterval('PT1S'))
        );
    }

    protected function ensurePrecisionMatches(Period $other): void
    {
        if ($this->precision->equals($other->precision)) {
            return;
        }

        throw CannotComparePeriods::precisionDoesNotMatch();
    }
}
