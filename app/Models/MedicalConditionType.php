<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalConditionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'severity',
        'requires_date',
        'is_active',
    ];

    protected $casts = [
        'requires_date' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get all clients that have this medical condition.
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_medical_condition')
            ->withPivot('occurred_at', 'notes', 'is_active')
            ->withTimestamps();
    }
}
