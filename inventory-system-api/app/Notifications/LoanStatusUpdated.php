<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoanStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;
    private $loan;
    private $admin;

    public function __construct($loan, $admin)
    {
        $this->loan = $loan;
        $this->admin = $admin;
    }

    public function via()
    {
        return ['database'];
    }

    public function toDatabase()
    {
        return [
            'type' => 'loan_status',
            'title' => 'Status Peminjaman Diperbarui',
            'message' => "Peminjaman aset {$this->loan->asset->asset_name} telah statusnya menjadi '{$this->loan->loan_status}' oleh Admin: {$this->admin->name}.",
            'data_id' => $this->loan->id_loan,
            'actor_id' => $this->admin->id_user,
            'timestamp' => now()
        ];
    }
}
