<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetImage extends Model
{
    use HasFactory;

    protected $table = 'asset_images';
    protected $primaryKey = 'filename';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'filename',
        'id_asset',
        'image_url',
        'description'
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'id_asset', 'id_asset');
    }
}
