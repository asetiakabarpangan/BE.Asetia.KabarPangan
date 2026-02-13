<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    protected $table = 'maintenances';
    protected $primaryKey = 'id_maintenance';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_maintenance',
        'id_asset',
        'id_maintenance_officer',
        'maintenance_status',
        'maintenance_date',
        'finish_date',
        'maintenance_detail',
        'maintenance_cost',
    ];

    protected $attributes = [
        'maintenance_status' => 'Dalam Perbaikan'
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'id_asset', 'id_asset');
    }

    public function maintenanceOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_maintenance_officer', 'id_user');
    }
}
