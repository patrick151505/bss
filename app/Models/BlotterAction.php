<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlotterAction extends Model
{
    protected $table = 'eb_blotter_actions';

    protected $fillable = [
        'blotter_id', 'action_type', 'scheduled_date', 'scheduled_time',
        'venue', 'conducted_by', 'outcome',
        'complainant_attendance', 'respondent_attendance',
        'notes', 'photos', 'created_by',
    ];

    protected $casts = [
        'scheduled_date'         => 'date',
        'complainant_attendance' => 'array',
        'respondent_attendance'  => 'array',
        'photos'                 => 'array',
    ];

    const ACTION_TYPES = [
        'hearing'       => 'Hearing',
        'summons'       => 'Summons',
        'mediation'     => 'Mediation',
        'conciliation'  => 'Conciliation',
        'investigation' => 'Investigation',
        'other'         => 'Other',
    ];

    const OUTCOMES = [
        'pending'     => 'Pending',
        'held'        => 'Held',
        'cancelled'   => 'Cancelled',
        'rescheduled' => 'Rescheduled',
    ];

    const ATTENDANCE = [
        'present' => 'Present',
        'absent'  => 'Absent',
    ];

    const ATTENDANCE_COLORS = [
        'present' => ['bg-green-100 text-green-800', 'bg-green-400'],
        'absent'  => ['bg-red-100 text-red-800',     'bg-red-400'],
    ];

    const OUTCOME_COLORS = [
        'pending'     => ['bg-yellow-100 text-yellow-800', 'bg-yellow-400'],
        'held'        => ['bg-green-100 text-green-800',   'bg-green-400'],
        'cancelled'   => ['bg-red-100 text-red-800',       'bg-red-400'],
        'rescheduled' => ['bg-gray-100 text-gray-800',     'bg-gray-400'],
    ];

    public function blotter()
    {
        return $this->belongsTo(Blotter::class, 'blotter_id');
    }

    public function versions()
    {
        return $this->hasMany(BlotterActionVersion::class, 'action_id')->orderByDesc('created_at');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actionLabel(): string
    {
        return self::ACTION_TYPES[$this->action_type] ?? ucfirst($this->action_type);
    }

    public function outcomeLabel(): string
    {
        return self::OUTCOMES[$this->outcome] ?? ucfirst($this->outcome);
    }

    public function outcomeBadge(): array
    {
        return self::OUTCOME_COLORS[$this->outcome] ?? ['bg-gray-100 text-gray-800', 'bg-gray-400'];
    }
}
