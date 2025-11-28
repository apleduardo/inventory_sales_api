<?php

namespace App\Exceptions;

use Exception;
use App\Models\Sale;

class SaleAlreadyRegisteredException extends Exception
{
    protected $sale;

    public function __construct(Sale $sale)
    {
        parent::__construct('Venda já registrada para este hash.');
        $this->sale = $sale;
    }

    public function getSale()
    {
        return $this->sale;
    }
}
