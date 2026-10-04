<?php

namespace Tests\Unit;

use App\Helpers\Common;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class UnaccountedDaysTest extends TestCase
{
    public function test_counts_unmarked_days_and_skips_future(): void
    {
        $this->assertSame(0, Common::unaccountedDays([], '2030-01-01', '2030-01-31')); // all future
        $rows = [(object) ['date' => '2020-01-01', 'Status' => 'Present'], ['date' => '2020-01-03', 'Status' => 'DayOff'], (object) ['date' => '2020-01-04', 'Status' => '']];
        // Jan 1-5: marked 1 and 3 -> 2,4,5 unaccounted
        $this->assertSame(3, Common::unaccountedDays($rows, '2020-01-01', '2020-01-05'));
    }
}
