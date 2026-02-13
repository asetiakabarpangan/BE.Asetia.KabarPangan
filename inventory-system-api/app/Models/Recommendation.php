<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    protected $table = 'recommendations';

    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'id_asset',
        'id_job_profile',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'id_asset', 'id_asset');
    }

    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class, 'id_job_profile', 'id_job_profile');
    }
}
