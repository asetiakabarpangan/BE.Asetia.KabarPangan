<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_images', function (Blueprint $table) {
            $table->string('filename', 255)->primary();
            $table->string('id_asset', 20);
            $table->foreign('id_asset')
                  ->references('id_asset')
                  ->on('assets')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
            $table->string('image_url', 255);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_images');
    }
};
