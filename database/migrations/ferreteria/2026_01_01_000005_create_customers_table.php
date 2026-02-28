<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->enum('type', ['publico', 'constructor', 'empresa', 'mayoreo'])->default('publico'); // Tipo de cliente
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('phone_alt', 20)->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('business_name', 200)->nullable(); // Razón social para factura
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('credit_limit', 10, 2)->default(0);   // Límite de crédito
            $table->decimal('credit_balance', 10, 2)->default(0); // Saldo pendiente
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('email');
            $table->index('phone');
            $table->index('rfc');
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
