<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';
    protected $primaryKey = 'id_location';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_location',
        'location_name',
        'building',
        'id_person_in_charge',
    ];

    public function personInCharge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_person_in_charge', 'id_user');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'id_location', 'id_location');
    }
}
