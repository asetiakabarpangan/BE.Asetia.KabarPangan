<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobProfile extends Model
{
    use HasFactory;

    protected $table = 'job_profiles';
    protected $primaryKey = 'id_job_profile';

    protected $fillable = [
        'profile_name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_job_profile', 'id_job_profile');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class, 'id_job_profile', 'id_job_profile');
    }

    public function procurements(): HasMany
    {
        return $this->hasMany(Procurement::class, 'id_job_profile_target', 'id_job_profile');
    }
}
