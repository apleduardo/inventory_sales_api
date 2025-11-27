<?php

namespace App\Services;

use App\Repositories\InventoryRepository;

class InventoryService
{
    protected $inventoryRepository;

    public function __construct(InventoryRepository $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    public function registerEntry(int $productId, int $quantity, float $costPrice)
    {
        // Validações de negócio adicionais podem ir aqui

        // Delega ao Repository a atualização atômica do DB
        return $this->inventoryRepository->updateStockEntry(
            $productId, 
            $quantity, 
            $costPrice
        );
    }
}