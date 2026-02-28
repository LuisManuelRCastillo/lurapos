<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');

            // Snapshot del producto al momento de la venta (por si se edita/elimina después)
            $table->string('product_sku', 50);
            $table->string('product_name', 200);
            $table->string('product_brand', 100)->nullable();
            $table->string('product_unit', 30)->default('pieza');

            // Cantidades y precios
            $table->decimal('quantity', 10, 3);              // Decimal para ventas por metro/kg
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);

            $table->timestamps();

            $table->index('sale_id');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_details');
    }
}
