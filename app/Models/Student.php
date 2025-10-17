<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'email',
        'phone',
        'parent_email',
        'parent_phone',
        'has_guardian',
        'guardian_email',
        'guardian_phone',
        'notify_student_email',
        'notify_parent_email',
        'notify_guardian_email',
        'notify_student_phone',
        'notify_parent_phone',
        'notify_guardian_phone',
        'package',
        'vehicle',
        'location',
    ];

    protected $appends = ['full_name'];

    protected $casts = [
        'birth_date' => 'date',
        'has_guardian' => 'boolean',
        'notify_student_email' => 'boolean',
        'notify_parent_email' => 'boolean',
        'notify_guardian_email' => 'boolean',
        'notify_student_phone' => 'boolean',
        'notify_parent_phone' => 'boolean',
        'notify_guardian_phone' => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
