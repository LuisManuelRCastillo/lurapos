<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryMovementsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');

            $table->enum('type', [
                'entrada',      // Compra / recepción de mercancía
                'salida',       // Salida manual (merma, donación, etc.)
                'venta',        // Reducción por venta
                'devolucion',   // Devolución de cliente (regresa al stock)
                'ajuste',       // Ajuste de inventario físico
                'traslado_in',  // Recepción desde otra sucursal
                'traslado_out', // Envío a otra sucursal
            ]);

            $table->decimal('quantity', 10, 3);  // Positivo = entrada, Negativo = salida
            $table->decimal('stock_before', 10, 3);
            $table->decimal('stock_after', 10, 3);
            $table->decimal('unit_cost', 10, 2)->nullable(); // Costo unitario en entradas

            // Referencia al documento origen
            $table->string('reference_type', 50)->nullable(); // 'sale', 'purchase', 'adjustment'
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('type');
            $table->index('branch_id');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_movements');
    }
}
