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
            $table->boolean('archived')->default(false)->comment('Sinaliza registros de estoque antigos para arquivamento');
            $table->index('archived', 'idx_inventory_levels_archived'); // Índice para consultas rápidas
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
    }
        
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_levels');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('products');
    }
};
