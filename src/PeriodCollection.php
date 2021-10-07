<?php

namespace Spatie\Period;

use ArrayAccess;
use Closure;
use Countable;
use Iterator;

class PeriodCollection implements ArrayAccess, Iterator, Countable
{
    use IterableImplementation;

    /** @var \Spatie\Period\Period[] */
    protected array $periods;

    /**
     * @return $this
     */
    public static function make(Period ...$periods)
    {
        return new static(...$periods);
    }

    public function __construct(Period ...$periods)
    {
        $this->periods = $periods;
    }

    public function current(): Period
    {
        return $this->periods[$this->position];
    }

    public function overlapAll(PeriodCollection ...$others): PeriodCollection
    {
        $overlap = clone $this;

        foreach ($others as $other) {
            $overlap = $overlap->overlap($other);
        }

        return $overlap;
    }

    public function boundaries(): ?Period
    {
        $start = null;
        $end = null;

        foreach ($this as $period) {
            if ($start === null || $start > $period->includedStart()) {
                $start = $period->includedStart();
            }

            if ($end === null || $end < $period->includedEnd()) {
                $end = $period->includedEnd();
            }
        }

        if (! $start || ! $end) {
            return null;
        }

        [$firstPeriod] = $this->periods;

        return Period::make(
            $start,
            $end,
            $firstPeriod->precision(),
            Boundaries::EXCLUDE_NONE()
        );
    }

    /**
     * @return $this
     */
    public function gaps()
    {
        $boundaries = $this->boundaries();

        if (! $boundaries) {
            return static::make();
        }

        return $boundaries->subtract(...$this);
    }

    /**
     * @return $this
     */
    public function intersect(Period $intersection)
    {
        $intersected = static::make();

        foreach ($this as $period) {
            $overlap = $intersection->overlap($period);

            if ($overlap === null) {
                continue;
            }

            $intersected[] = $overlap;
        }

        return $intersected;
    }

    /**
     * @return $this
     */
    public function add(Period ...$periods)
    {
        $collection = clone $this;

        foreach ($periods as $period) {
            $collection[] = $period;
        }

        return $collection;
    }

    /**
     * @return $this
     */
    public function map(Closure $closure)
    {
        $collection = clone $this;

        foreach ($collection->periods as $key => $period) {
            $collection->periods[$key] = $closure($period);
        }

        return $collection;
    }

    /**
     * @return mixed
     */
    public function reduce(Closure $closure, $initial = null)
    {
        $carry = $initial;

        foreach ($this as $period) {
            $carry = $closure($carry, $period);
        }

        return $carry;
    }

    /**
     * @return $this
     */
    public function filter(Closure $closure)
    {
        $collection = clone $this;

        $collection->periods = array_values(array_filter($collection->periods, $closure));

        return $collection;
    }

    public function isEmpty(): bool
    {
        return count($this->periods) === 0;
    }

    /**
     * @param \Spatie\Period\PeriodCollection|\Spatie\Period\Period $others
     * @return $this
     */
    public function subtract($others)
    {
        if ($others instanceof Period) {
            $others = new static($others);
        }

        if ($others->count() === 0) {
            return clone $this;
        }

        $collection = new static();

        foreach ($this as $period) {
            $collection = $collection->add(...$period->subtract(...$others));
        }

        return $collection;
    }

    private function overlap(PeriodCollection $others): PeriodCollection
    {
        $overlaps = new PeriodCollection();

        foreach ($this as $period) {
            foreach ($others as $other) {
                if (! $period->overlap($other)) {
                    continue;
                }

                $overlaps[] = $period->overlap($other);
            }
        }

        return $overlaps;
    }
}
