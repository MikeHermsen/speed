<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'student_id',
        'status',
        'start_time',
        'end_time',
        'vehicle',
        'package',
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
        'location',
        'description',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'has_guardian' => 'boolean',
        'notify_student_email' => 'boolean',
        'notify_parent_email' => 'boolean',
        'notify_guardian_email' => 'boolean',
        'notify_student_phone' => 'boolean',
        'notify_parent_phone' => 'boolean',
        'notify_guardian_phone' => 'boolean',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
