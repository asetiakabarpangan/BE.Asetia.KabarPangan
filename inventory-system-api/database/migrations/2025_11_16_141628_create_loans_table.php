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
        Schema::create('loans', function (Blueprint $table) {
            $table->string('id_loan')->primary();
            $table->string('id_asset', 20);
            $table->foreign('id_asset')
                  ->references('id_asset')
                  ->on('assets')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
            $table->string('id_user', 20);
            $table->dateTime('borrow_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('return_date')->nullable();
            $table->enum('loan_status', [
                'Menunggu Konfirmasi Peminjaman',
                'Dipinjam',
                'Peminjaman Ditolak',
                'Menunggu Konfirmasi Pengembalian',
                'Dikembalikan'
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
