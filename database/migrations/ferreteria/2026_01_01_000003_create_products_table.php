<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // --- Identificación ---
            $table->string('sku', 50)->unique();             // Código interno de la ferretería
            $table->string('supplier_code', 80)->nullable(); // Código del proveedor (ej: clave Truper)
            $table->string('barcode', 100)->nullable();      // Código de barras EAN/UPC
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null'); // Proveedor principal

            // --- Descripción ---
            $table->string('name', 200);
            $table->string('brand', 100)->nullable();        // Marca (Truper, Urrea, Stanley, etc.)
            $table->string('model', 100)->nullable();        // Modelo del fabricante
            $table->string('size', 100)->nullable();         // Medida / presentación (1/2", 3 kg, 100 pzas)
            $table->string('unit', 30)->default('pieza');    // Unidad de venta: pieza, metro, kg, litro, caja, rollo
            $table->text('description')->nullable();
            $table->text('specs')->nullable();               // Especificaciones técnicas (JSON o texto libre)

            // --- Dimensiones y peso (útil para envíos y almacén) ---
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();

            // --- Inventario ---
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(5);        // Punto de reorden
            $table->integer('max_stock')->nullable();        // Stock máximo deseado
            $table->string('location', 100)->nullable();     // Ubicación en almacén (ej: "Estante A-3")

            // --- Precios ---
            $table->decimal('cost_price', 10, 2)->default(0);       // Costo de compra
            $table->decimal('sale_price', 10, 2);                    // Precio de venta al público
            $table->decimal('wholesale_price', 10, 2)->nullable();   // Precio mayoreo
            $table->decimal('min_sale_price', 10, 2)->nullable();    // Precio mínimo permitido

            // --- Imagen principal ---
            $table->string('image', 500)->nullable();        // Ruta o URL de imagen principal

            // --- Control ---
            $table->boolean('active')->default(true);
            $table->boolean('photo_verified')->default(false); // Indica si la foto fue validada manualmente
            $table->timestamps();
            $table->softDeletes();

            // --- Índices ---
            $table->index('sku');
            $table->index('supplier_code');
            $table->index('barcode');
            $table->index('category_id');
            $table->index('supplier_id');
            $table->index('brand');
            $table->index('active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
