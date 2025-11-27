<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

/**
 * DTO para registrar uma nova venda (POST /api/v1/sales)
 */
class SalesEntryDTO extends Data
{
    /**
     * @param string|null $customer_name Nome do cliente (opcional)
     * @param array $items Array de itens da venda
     */
    public function __construct(
        public ?string $customer_name,
        public array $items,
    ) {}

    /**
     * Define as regras de validação para o DTO.
     */
    public static function rules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
