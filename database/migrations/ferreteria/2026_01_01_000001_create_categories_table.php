<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null'); // Subcategorías
            $table->string('name', 100);
            $table->string('slug', 120)->unique(); // URL amigable
            $table->string('description', 255)->nullable();
            $table->string('icon', 50)->nullable(); // Ícono (clase CSS o nombre)
            $table->integer('sort_order')->default(0); // Orden de visualización
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
}
