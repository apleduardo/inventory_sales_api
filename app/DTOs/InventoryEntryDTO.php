<?php

namespace App\DTOs;

use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Data;

/**
 * DTO para registrar uma nova entrada de estoque (POST /api/inventory)
 */
class InventoryEntryDTO extends Data
{
    /**
     * @param int $product_id O ID do produto que está entrando no estoque.
     * @param int $quantity A quantidade de unidades que estão entrando.
     * @param float $cost_price O custo unitário desta aquisição.
     */
    public function __construct(
        public int $product_id,
        public int $quantity,
        public float $cost_price,
    ) {}

    /**
     * Define as regras de validação para o DTO.
     */
    public static function rules(): array
    {
        return [
            // O ID do produto deve existir na tabela 'products'
            'product_id' => ['required', 'integer', 'exists:products,id'],
            
            // A quantidade deve ser um número inteiro e maior que zero (entrada)
            'quantity' => ['required', 'integer', 'min:1'],
            
            // O preço de custo deve ser numérico e não negativo
            'cost_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}