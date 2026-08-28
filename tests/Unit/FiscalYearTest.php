<?php

namespace Tests\Unit;

use App\Support\FiscalYear;
use Tests\TestCase;

class FiscalYearTest extends TestCase
{
    public function test_start_year_is_the_calendar_year_from_april_onward(): void
    {
        $this->travelTo('2026-08-28 10:00:00');

        $this->assertSame(2026, FiscalYear::startYear());
        $this->assertSame('Apr 2026', FiscalYear::months()[0]['label']);
        $this->assertSame('Mar 2027', FiscalYear::months()[11]['label']);
    }

    public function test_start_year_rolls_back_before_april(): void
    {
        $this->travelTo('2027-03-15 10:00:00');

        $this->assertSame(2026, FiscalYear::startYear());
    }
}
