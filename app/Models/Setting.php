<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'eb_settings';

    protected $fillable = [
        'barangay_name',
        'address',
        'municipality',
        'province',
        'contact',
        'email',
        'logo',
        'municipal_logo',
        'captain_name',
        'captain_position',
        'citizen_id_prefix',
        'citizen_id_digits',
    ];

    public static function instance(): static
    {
        return static::firstOrCreate(['id' => 1], ['captain_position' => 'Barangay Captain']);
    }

    public function formatCitizenId(int $id): string
    {
        $prefix = $this->citizen_id_prefix ?: 'EBT';
        $digits = max(1, (int) ($this->citizen_id_digits ?: 6));
        return $prefix . '-' . str_pad($id, $digits, '0', STR_PAD_LEFT);
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address,
            $this->municipality,
            $this->province,
        ])->filter()->implode(', ');
    }
}
