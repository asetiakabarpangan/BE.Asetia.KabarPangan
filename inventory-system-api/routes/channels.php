<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin-updates', function ($user) {
    if(!$user) return false;
    return in_array($user->role, ['admin', 'moderator']);
});

Broadcast::channel('employee-updates', function ($user) {
    if(!$user) return false;
    return $user->role === 'employee';
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    if(!$user) return false;
    return (string) $user->id_user === (string) $userId;
});
