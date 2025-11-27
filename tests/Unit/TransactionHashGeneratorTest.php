<?php

namespace Tests\Unit;

use App\Utils\TransactionHashGenerator;
use PHPUnit\Framework\TestCase;

class TransactionHashGeneratorTest extends TestCase
{
    public function test_generate_hash_is_consistent_for_same_data()
    {
        $data = [
            'customer_name' => 'Cliente Teste',
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'unit_price' => 10.0]
            ]
        ];
        $hash1 = TransactionHashGenerator::generate($data);
        $hash2 = TransactionHashGenerator::generate($data);
        $this->assertEquals($hash1, $hash2);
    }

    public function test_generate_hash_is_different_for_different_data()
    {
        $data1 = [
            'customer_name' => 'Cliente Teste',
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'unit_price' => 10.0]
            ]
        ];
        $data2 = [
            'customer_name' => 'Outro Cliente',
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'unit_price' => 10.0]
            ]
        ];
        $hash1 = TransactionHashGenerator::generate($data1);
        $hash2 = TransactionHashGenerator::generate($data2);
        $this->assertNotEquals($hash1, $hash2);
    }
}
