<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlotterActionVersion extends Model
{
    protected $table = 'eb_blotter_action_versions';

    public $timestamps = false;

    protected $fillable = [
        'action_id', 'blotter_id', 'outcome',
        'complainant_attendance', 'respondent_attendance',
        'notes', 'photos', 'saved_by',
    ];

    protected $casts = [
        'complainant_attendance' => 'array',
        'respondent_attendance'  => 'array',
        'photos'                 => 'array',
        'created_at'             => 'datetime',
    ];

    public function savedBy()
    {
        return $this->belongsTo(User::class, 'saved_by');
    }

    public function outcomeLabel(): string
    {
        return BlotterAction::OUTCOMES[$this->outcome] ?? ucfirst($this->outcome);
    }

    public function outcomeBadge(): array
    {
        return BlotterAction::OUTCOME_COLORS[$this->outcome] ?? ['bg-gray-100 text-gray-800', 'bg-gray-400'];
    }
}
