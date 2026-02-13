<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';
    protected $primaryKey = 'id_department';

    protected $fillable = [
        'department_name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_department', 'id_department');
    }
}
