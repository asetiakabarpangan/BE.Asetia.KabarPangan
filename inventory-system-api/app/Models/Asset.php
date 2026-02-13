<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class Asset extends Model
{
    use HasFactory;

    protected $table = 'assets';
    protected $primaryKey = 'id_asset';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_asset',
        'id_category',
        'asset_name',
        'brand',
        'specification',
        'id_location',
        'condition',
        'acquisition_date',
        'availability_status',
        'information',
    ];

    protected $casts = [
        'specification' => 'array',
        'acquisition_date' => 'date',
    ];

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class, 'id_asset', 'id_asset');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_category', 'id_category');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'id_location', 'id_location');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'id_asset', 'id_asset');
    }

    public function images(): HasMany
    {
        return $this->hasMany(AssetImage::class, 'id_asset', 'id_asset');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'id_asset', 'id_asset');
    }
}
