<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['actor_id', 'action', 'model_name', 'data_name', 'message'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id', 'id_user');
    }
}
