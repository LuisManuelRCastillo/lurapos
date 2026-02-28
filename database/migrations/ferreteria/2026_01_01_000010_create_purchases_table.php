<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();   // Folio interno (OC-000001)
            $table->string('supplier_invoice', 100)->nullable(); // Número de factura del proveedor
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');

            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->enum('status', ['pendiente', 'recibida', 'parcial', 'cancelada'])->default('pendiente');
            $table->enum('payment_status', ['pendiente', 'pagada', 'credito'])->default('pendiente');

            $table->date('order_date');
            $table->date('expected_date')->nullable();  // Fecha esperada de entrega
            $table->date('received_date')->nullable();  // Fecha real de recepción

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_number');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('order_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchases');
    }
}
