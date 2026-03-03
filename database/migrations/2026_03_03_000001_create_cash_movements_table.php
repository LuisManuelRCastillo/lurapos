<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_movements')) return;

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['entrada', 'salida'])->default('salida');
            $table->string('concept');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
