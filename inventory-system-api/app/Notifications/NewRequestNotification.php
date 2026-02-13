<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;
    private $data;
    private $type;

    public function __construct($data, string $type)
    {
        $this->data = $data;
        $types = [
            'loan' => 'Peminjaman',
            'procurement' => 'Pengajuan Pengadaan',
        ];
        $this->type = $types[$type] ?? 'Permintaan Tidak Dikenal';
    }

    public function via()
    {
        return ['database'];
    }

    public function toDatabase()
    {
        $requesterName = $this->data->user->name ?? $this->data->requester->name;
        $status = $this->data->loan_status ?? $this->data->procurement_status;
        $itemName = $this->data->asset->asset_name ?? $this->data->item_name;
        $types = [
            'Peminjaman' => "User {$requesterName} sedang ($status) untuk {$this->type}: {$itemName}.",
            'Pengajuan Pengadaan' => "User {$requesterName} mengajukan {$this->type} untuk: {$itemName}.",
        ];
        return [
            'type' => 'new_request',
            'title' => 'Permintaan Baru Masuk',
            'message' => $types[$this->type] ?? "Ada permintaan baru untuk {$this->type}.",
            'data_id' => $this->type === 'loan' ? $this->data->id_loan : $this->data->id_procurement,
            'timestamp' => now()
        ];
    }
}
