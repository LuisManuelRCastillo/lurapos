<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega image y photo_verified a la tabla real 'productos' de rodcas.
 * La migración anterior (2026_02_23) apuntaba a 'products' (tabla equivocada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'image')) {
                $table->string('image', 500)->nullable()->after('dpto');
            }
            if (! Schema::hasColumn('productos', 'photo_verified')) {
                $table->boolean('photo_verified')->default(false)->after('image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('productos', 'photo_verified')) $cols[] = 'photo_verified';
            if (Schema::hasColumn('productos', 'image'))          $cols[] = 'image';
            if ($cols) $table->dropColumn($cols);
        });
    }
};
