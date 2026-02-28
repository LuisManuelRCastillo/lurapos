<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el campo photo_verified a la tabla products existente.
 * Ejecutar con: php artisan migrate
 */
class AddPhotoVerifiedToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('photo_verified')->default(false)->after('image');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('photo_verified');
        });
    }
}
