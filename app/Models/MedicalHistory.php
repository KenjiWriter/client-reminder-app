<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'is_pregnant',
        'has_epilepsy',
        'has_thyroid_issues',
        'has_cancer',
        'has_herpes',
        'has_botox',
        'botox_last_date',
        'has_fillers',
        'fillers_last_date',
        'has_threads',
        'allergies',
        'medications',
        'additional_notes',
    ];

    protected $casts = [
        'is_pregnant' => 'boolean',
        'has_epilepsy' => 'boolean',
        'has_thyroid_issues' => 'boolean',
        'has_cancer' => 'boolean',
        'has_herpes' => 'boolean',
        'has_botox' => 'boolean',
        'botox_last_date' => 'date',
        'has_fillers' => 'boolean',
        'fillers_last_date' => 'date',
        'has_threads' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
