<?php

namespace App\Utils;

use Carbon\Carbon;

class DateFilterHelper
{
    /**
     * Normaliza uma data de filtro para o formato correto
     * Se vier só a data, converte para início/fim do dia
     */
    public static function normalizeStart($date)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Carbon::parse($date)->startOfDay()->format('Y-m-d H:i:s');
        }
        return $date;
    }

    public static function normalizeEnd($date)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Carbon::parse($date)->endOfDay()->format('Y-m-d H:i:s');
        }
        return $date;
    }
}
