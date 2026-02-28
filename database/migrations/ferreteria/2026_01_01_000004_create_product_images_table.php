<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de imágenes múltiples por producto.
 * Permite tener foto principal + fotos adicionales (ángulos, detalles, etc.)
 * La imagen principal también se guarda en products.image para acceso rápido.
 */
class CreateProductImagesTable extends Migration
{
    public function up()
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('path', 500);                      // Ruta local en storage
            $table->string('source_url', 500)->nullable();    // URL original (ej: banco Truper)
            $table->enum('source', ['upload', 'url', 'truper', 'google'])->default('upload');
            $table->string('alt_text', 200)->nullable();      // Texto alternativo (accesibilidad / SEO)
            $table->boolean('is_primary')->default(false);    // Foto principal del producto
            $table->boolean('verified')->default(false);      // Validada manualmente por el usuario
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->index('is_primary');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_images');
    }
}
