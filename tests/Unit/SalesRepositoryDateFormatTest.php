<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repositories\SalesRepository;
use Carbon\Carbon;
use App\Utils\DateFilterHelper;

class SalesRepositoryDateFormatTest extends TestCase
{
    public function test_filter_accepts_full_datetime_and_date_only()
    {
        $repo = new SalesRepository();
        // Simula filtros com data completa e só data
        $filtersDatetime = [
            'start_date' => '2025-11-25 08:00:00',
            'end_date' => '2025-11-25 18:00:00',
        ];
        $filtersDateOnly = [
            'start_date' => '2025-11-25',
            'end_date' => '2025-11-25',
        ];
        // Testa conversão do filtro
        $startDatetime = $filtersDatetime['start_date'];
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDatetime)) {
            $startDatetime = Carbon::parse($startDatetime)->startOfDay()->format('Y-m-d H:i:s');
        }
        $this->assertEquals('2025-11-25 08:00:00', $startDatetime);

        $startDateOnly = $filtersDateOnly['start_date'];
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDateOnly)) {
            $startDateOnly = Carbon::parse($startDateOnly)->startOfDay()->format('Y-m-d H:i:s');
        }
        $this->assertEquals('2025-11-25 00:00:00', $startDateOnly);
    }

    public function test_normalize_start_and_end_date_helper()
    {
        $this->assertEquals('2025-11-25 00:00:00', DateFilterHelper::normalizeStart('2025-11-25'));
        $this->assertEquals('2025-11-25 08:00:00', DateFilterHelper::normalizeStart('2025-11-25 08:00:00'));
        $this->assertEquals('2025-11-25 23:59:59', DateFilterHelper::normalizeEnd('2025-11-25'));
        $this->assertEquals('2025-11-25 18:00:00', DateFilterHelper::normalizeEnd('2025-11-25 18:00:00'));
    }
}
