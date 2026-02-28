<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersTable extends Migration
{
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('trade_name', 150)->nullable(); // Nombre comercial / razón social
            $table->string('rfc', 13)->nullable(); // RFC del proveedor
            $table->string('contact_name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('phone_alt', 20)->nullable(); // Teléfono alternativo
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('website', 255)->nullable(); // Sitio web / catálogo
            $table->string('payment_terms', 100)->nullable(); // Ej: "30 días", "contado"
            $table->integer('delivery_days')->nullable(); // Días promedio de entrega
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('name');
            $table->index('active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
}
