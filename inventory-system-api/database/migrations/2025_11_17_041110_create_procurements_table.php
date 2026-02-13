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
        Schema::create('procurements', function (Blueprint $table) {
            $table->string('id_procurement')->primary();
            $table->string('id_requester', 20);
            $table->foreign('id_requester')
                  ->references('id_user')
                  ->on('users')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('id_job_profile_target')->nullable();
            $table->foreign('id_job_profile_target')
                  ->references('id_job_profile')
                  ->on('job_profiles')
                  ->nullOnDelete();
            $table->string('item_name');
            $table->text('desired_specifications');
            $table->integer('quantity');
            $table->text('reason');
            $table->date('request_date');
            $table->enum('procurement_status', [
                'Diajukan',
                'Disetujui',
                'Ditolak',
                'Selesai'
            ]);
            $table->string('id_approver', 20)->nullable();
            $table->foreign('id_approver')
                  ->references('id_user')
                  ->on('users')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();
            $table->date('action_date')->nullable();
            $table->text('approver_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurements');
    }
};
