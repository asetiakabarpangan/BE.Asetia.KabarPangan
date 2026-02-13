<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->string('id_asset', 20)->primary();
            $table->string('id_category', 10)->nullable();
            $table->foreign('id_category')
                  ->references('id_category')
                  ->on('categories')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();
            $table->string('asset_name');
            $table->string('brand');
            $table->json('specification');
            $table->string('id_location', 20)->nullable();
            $table->foreign('id_location')
                  ->references('id_location')
                  ->on('locations')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();
            $table->enum('condition', ['Baik','Rusak','Hilang']);
            $table->date('acquisition_date');
            $table->enum('availability_status', [
                'Tersedia',
                'Dipinjam',
                'Dalam Perbaikan'
            ]);
            $table->text('information')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
