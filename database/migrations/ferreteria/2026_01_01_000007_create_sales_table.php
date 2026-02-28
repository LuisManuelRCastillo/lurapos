<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique(); // Folio (FAC-000001)
            $table->foreignId('user_id')->constrained()->onDelete('restrict');          // Vendedor
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');

            // Totales
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);       // IVA
            $table->decimal('total', 10, 2);

            // Pago
            $table->enum('payment_method', ['efectivo', 'tarjeta', 'transferencia', 'credito', 'mixto'])->default('efectivo');
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('change_amount', 10, 2)->default(0);

            // Crédito (para clientes con cuenta)
            $table->boolean('is_credit')->default(false);
            $table->date('due_date')->nullable(); // Fecha límite de pago crédito

            // Estado
            $table->enum('status', ['completada', 'cancelada', 'pendiente', 'credito'])->default('completada');
            $table->text('notes')->nullable();
            $table->timestamp('sale_date');

            // Facturación
            $table->boolean('requires_invoice')->default(false); // Necesita factura fiscal
            $table->boolean('email_sent')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_number');
            $table->index('sale_date');
            $table->index('user_id');
            $table->index('branch_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales');
    }
}
