<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Procurement extends Model
{
    use HasFactory;

    protected $table = 'procurements';
    protected $primaryKey = 'id_procurement';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_procurement',
        'id_requester',
        'id_job_profile_target',
        'item_name',
        'desired_specifications',
        'quantity',
        'reason',
        'request_date',
        'procurement_status',
        'id_approver',
        'action_date',
        'approver_notes',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_requester', 'id_user');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_approver', 'id_user');
    }

    public function jobProfileTarget(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class, 'id_job_profile_target', 'id_job_profile');
    }
}
