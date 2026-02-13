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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->string('id_maintenance')->primary();
            $table->string('id_asset', 20)->nullable();
            $table->foreign('id_asset')
                ->references('id_asset')
                ->on('assets')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('id_maintenance_officer', 20);
            $table->enum('maintenance_status', ['Dalam Perbaikan', 'Selesai']);
            $table->date('maintenance_date');
            $table->date('finish_date')->nullable();
            $table->text('maintenance_detail');
            $table->decimal('maintenance_cost', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
