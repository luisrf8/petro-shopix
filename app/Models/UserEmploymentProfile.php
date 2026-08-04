<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEmploymentProfile extends Model
{
    use HasFactory;

    protected $table = 'user_employment_profiles';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'contract_file_path',
        'family_dependents',
        'hired_at',
        'birth_date',
        'age',
        'employment_type',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'hired_at' => 'date',
        'birth_date' => 'date',
        'family_dependents' => 'integer',
        'age' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
