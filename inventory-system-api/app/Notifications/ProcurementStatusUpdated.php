<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcurementStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    private $procurement;
    private $admin;

    public function __construct($procurement, $admin)
    {
        $this->procurement = $procurement;
        $this->admin = $admin;
    }

    public function via()
    {
        return ['database'];
    }

    public function toDatabase()
    {
        return [
            'type' => 'procurement_status',
            'title' => 'Status Pengajuan Diperbarui',
            'message' => "Pengajuan aset {$this->procurement->item_name} telah statusnya menjadi '{$this->procurement->procurement_status}' oleh Admin: {$this->admin->name}.",
            'data_id' => $this->procurement->id_procurement,
            'actor_id' => $this->admin->id_user,
            'timestamp' => now()
        ];
    }
}
