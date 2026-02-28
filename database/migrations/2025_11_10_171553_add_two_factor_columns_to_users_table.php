<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esta migración es un duplicado de 2025_11_10_171028.
 * Se conserva para no romper el historial de migraciones,
 * pero verifica si las columnas ya existen antes de agregarlas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')
                    ->after('password')
                    ->nullable();
            }

            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')
                    ->after('two_factor_secret')
                    ->nullable();
            }

            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')
                    ->after('two_factor_recovery_codes')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        // No hace nada: la migración original (171028) maneja el rollback
    }
};
