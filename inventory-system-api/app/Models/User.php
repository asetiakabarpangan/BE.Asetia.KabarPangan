<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    protected $table = 'users';
    protected $primaryKey = 'id_user';
    public $incrementing = false;
    protected $keyType = 'string';

    public function getKey()
    {
        return $this->id_user;
    }

    protected $fillable = [
        'id_user',
        'name',
        'email',
        'position',
        'id_department',
        'id_job_profile',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class, 'id_job_profile', 'id_job_profile');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'id_department', 'id_department');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'id_user', 'id_user');
    }

    public function managedLocations(): HasMany
    {
        return $this->hasMany(Location::class, 'id_person_in_charge', 'id_user');
    }

    public function maintenancesOfficer(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'id_maintenance_officer', 'id_user');
    }

    public function procurementsRequester(): HasMany
    {
        return $this->hasMany(Procurement::class, 'id_requester', 'id_user');
    }

    public function procurementsApprover(): HasMany
    {
        return $this->hasMany(Procurement::class, 'id_approver', 'id_user');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }
}
