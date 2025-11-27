<?php

namespace App\Utils;

class TransactionHashGenerator
{
    /**
     * Gera o hash de idempotência para uma venda
     * @param array $data Dados da venda (customer_name, items)
     * @return string
     */
    public static function generate(array $data): string
    {
        // Pode ser ajustado para usar outros algoritmos ou salt
        return md5(json_encode($data));
    }
}
