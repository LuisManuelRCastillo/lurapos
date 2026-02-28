<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración específica para rodcas.
 * Crea las tablas de ventas con FK a "productos" (no "products").
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Clientes (necesaria para FK opcional en sales) ──────────────
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('email', 150)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('rfc', 13)->nullable();
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('postal_code', 10)->nullable();
                $table->timestamps();
                $table->index('email');
                $table->index('phone');
            });
        }

        // ── 2. Ventas ──────────────────────────────────────────────────────
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 50)->unique();
                $table->foreignId('user_id')->constrained()->onDelete('restrict');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('subtotal', 10, 2);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('total', 10, 2);
                $table->enum('payment_method', ['efectivo', 'tarjeta', 'transferencia', 'mixto'])->default('efectivo');
                $table->decimal('amount_paid', 10, 2);
                $table->decimal('change_amount', 10, 2)->default(0);
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->enum('status', ['completada', 'cancelada', 'pendiente'])->default('completada');
                $table->text('notes')->nullable();
                $table->timestamp('sale_date');
                $table->boolean('email_sent')->default(false);
                $table->timestamps();
                $table->softDeletes();
                $table->index('invoice_number');
                $table->index('sale_date');
                $table->index('user_id');
            });
        }

        // ── 3. Detalles de venta (FK a "productos", no "products") ─────────
        if (!Schema::hasTable('sale_details')) {
            Schema::create('sale_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->onDelete('cascade');
                // FK explícita a "productos" (int(11) en rodcas)
                $table->integer('product_id');
                $table->foreign('product_id')
                      ->references('id')->on('productos')
                      ->onDelete('restrict');
                $table->string('product_code', 50);
                $table->string('product_name', 200);
                $table->integer('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('subtotal', 10, 2);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('total', 10, 2);
                $table->timestamps();
                $table->index('sale_id');
                $table->index('product_id');
            });
        }

        // ── 4. Movimientos de inventario (FK a "productos") ────────────────
        if (!Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                // FK explícita a "productos" (int(11) en rodcas)
                $table->integer('product_id');
                $table->foreign('product_id')
                      ->references('id')->on('productos')
                      ->onDelete('restrict');
                $table->foreignId('user_id')->constrained()->onDelete('restrict');
                $table->enum('type', ['entrada', 'salida', 'ajuste', 'venta', 'devolucion']);
                $table->integer('quantity');
                $table->integer('stock_before');
                $table->integer('stock_after');
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('movement_date');
                $table->timestamps();
                $table->index('product_id');
                $table->index('movement_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('sale_details');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};
