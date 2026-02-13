<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class CleanExpiredTokens extends Command
{
    /**
     * Nama dan signature command console.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup';

    /**
     * Deskripsi command.
     *
     * @var string
     */
    protected $description = 'Menghapus token Sanctum yang sudah expired dari database';

    /**
     * Eksekusi console command.
     */
    public function handle()
    {
        $deleted = PersonalAccessToken::where('expires_at', '<', now())->delete();
        if ($deleted > 0) {
            $this->info("Berhasil membersihkan {$deleted} token yang expired.");
        } else {
            $this->info("Tidak ada token expired yang ditemukan.");
        }
    }
}
