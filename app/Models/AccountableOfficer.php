<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountableOfficer extends Model
{
    protected $table = 'eb_accountable_officers';

    protected $fillable = [
        'name', 'position', 'fidelity_bond_amount', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'fidelity_bond_amount' => 'decimal:2',
    ];

    public function cashAdvances()
    {
        return $this->hasMany(CashAdvance::class, 'officer_id');
    }

    /** Returns the open (unliquidated) cash advance for this officer, if any. */
    public function openCashAdvance(): ?CashAdvance
    {
        return $this->cashAdvances()->where('status', 'open')->latest()->first();
    }

    public function hasOpenAdvance(): bool
    {
        return $this->cashAdvances()->where('status', 'open')->exists();
    }
}
