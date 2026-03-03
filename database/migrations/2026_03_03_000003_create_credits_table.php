<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credits')) return;

        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name', 150)->default('Cliente');
            $table->decimal('original_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->enum('status', ['pendiente', 'pagado'])->default('pendiente');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_id');
            $table->index('customer_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
