<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $module;
    public string $action;
    public $targetRole;
    public $targetUserId;

    public function __construct(
        string $module,
        string $action,
        string $targetRole = 'admin',
        string $targetUserId = ''
    ) {
        $this->module = $module;
        $this->action = $action;
        $this->targetRole = $targetRole;
        $this->targetUserId = $targetUserId;
    }

    public function broadcastOn(): array
    {
        $channels = [];
        if (in_array($this->targetRole, ['admin', 'all'])) {
            $channels[] = new PrivateChannel('admin-updates');
        }
        if (in_array($this->targetRole, ['employee', 'all'])) {
            $channels[] = new PrivateChannel('employee-updates');
        }
        if ($this->targetUserId) {
            $channels[] = new PrivateChannel("user.{$this->targetUserId}");
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'data.changed';
    }
}
