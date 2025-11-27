<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique()->comment('Código de identificação do produto (SKU)');
            $table->string('name', 255);
            $table->decimal('cost_price', 10, 2)->comment('Preço de custo atual. Usado para o cálculo simplificado do valor do estoque.');
            $table->decimal('sale_price', 10, 2)->comment('Preço de venda sugerido.');
            $table->timestamps();
        });

        Schema::create('inventory_levels', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained(
                table: 'products', indexName: 'idx_inventory_levels_product'
            )->onDelete('cascade');
            $table->unsignedBigInteger('quantity')->default(0)->comment('Quantidade atual em estoque');
            $table->timestamps();
            $table->index('updated_at', 'idx_inventory_levels_updated_at'); // Índice para auditoria por data
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained(
                table: 'products', indexName: 'idx_inventory_movements_product'
            )->onDelete('cascade');
            $table->enum('movement_type', ['IN', 'OUT'])->index();
            $table->unsignedInteger('quantity');
            $table->decimal('cost_price', 10, 2)->comment('Custo da unidade no momento da movimentação.');

            $table->timestamp('created_at')->index(); // Índice para auditoria por data
            $table->unique(['product_id', 'created_at'], 'movement_unique_per_second'); // Proteção extra contra duplicidade no mesmo produto/segundo
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();
            
            // Campo crucial para Idempotência. Deve ser único.
            $table->string('transaction_hash', 64)->unique()->comment('Hash para garantir idempotência do Job.');
            
            $table->decimal('total_amount', 10, 4)->comment('Valor total da venda.');
            $table->decimal('total_profit', 10, 4)->comment('Lucro total calculado.');
            
            // Status para o fluxo assíncrono
            $table->string('status')->default('PENDING')->index()->comment('PENDING, COMPLETED, FAILED');
            
            $table->timestamp('created_at')->index(); // Índice para relatórios por período
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2)->comment('Preço de venda unitário no momento da transação.');
            $table->decimal('cost_price', 10, 2)->comment('Custo unitário no momento da transação.');
            $table->decimal('profit', 10, 2)->comment('Lucro total da linha de item.');
            
            // 1. Índice no sale_id (FK é opcional, mas garante performance)
            $table->index('sale_id', 'idx_sale_items_sale_id');
            
            // 2. Índice no product_id (Crucial para relatórios)
            $table->index('product_id', 'idx_sale_items_product_id'); 
            
            // 3. Índice Composto (Otimização de buscas)
            $table->index(['sale_id', 'product_id'], 'idx_sale_items_sale_product_compound');
        });
    }
        
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('inventory_levels');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('sale_items');
    }
};
