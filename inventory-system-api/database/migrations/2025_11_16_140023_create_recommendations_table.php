<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->string('id_asset', 20);
            $table->foreign('id_asset')
                  ->references('id_asset')
                  ->on('assets')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
            $table->foreignId('id_job_profile')
                  ->constrained('job_profiles', 'id_job_profile')
                  ->cascadeOnDelete();
            $table->primary(['id_asset', 'id_job_profile']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
