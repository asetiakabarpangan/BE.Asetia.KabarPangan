<?php

namespace App\Traits;

use App\Models\{User, Location};
use Illuminate\Support\Facades\Notification;

trait NotifiesManagement
{
    protected function notifyRelevantUsers($notification, string $locationId): void
    {
        $recipients = User::where('role', 'admin')->get();
        $location = Location::find($locationId);
        if ($location && $location->id_person_in_charge) {
            $moderator = User::find($location->id_person_in_charge);
            if ($moderator && !$recipients->contains('id_user', $moderator->id_user)) {
                $recipients->push($moderator);
            }
        }
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }
    }
}
