<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');

            // Snapshot del producto
            $table->string('product_sku', 50);
            $table->string('product_name', 200);
            $table->string('supplier_code', 80)->nullable(); // Clave del proveedor en este pedido

            $table->decimal('quantity_ordered', 10, 3);      // Cantidad pedida
            $table->decimal('quantity_received', 10, 3)->default(0); // Cantidad recibida (para entregas parciales)
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total', 10, 2);

            $table->timestamps();

            $table->index('purchase_id');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_details');
    }
}
