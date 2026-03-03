<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL no permite agregar valores a ENUM con Blueprint::change()
        // Se usa SQL directo para modificar el ENUM
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method
            ENUM('efectivo','tarjeta','transferencia','mixto','credito')
            NOT NULL DEFAULT 'efectivo'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method
            ENUM('efectivo','tarjeta','transferencia','mixto')
            NOT NULL DEFAULT 'efectivo'");
    }
};
