<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One health check-up visit (libreng check-up or walk-in consultation).
 *
 * The patient is a citizen, or a walk-in typed by name (patient_name/age/sex).
 * Vital-sign readings are classified for at-a-glance flags; these are screening
 * guides only, not a diagnosis.
 */
class HealthCheckup extends Model
{
    use SoftDeletes;

    protected $table = 'eb_health_checkups';

    protected $fillable = [
        'citizen_id', 'patient_name', 'patient_age', 'patient_sex', 'checked_at', 'checkup_type', 'attended_by',
        'prescriber_license', 'prescriber_ptr',
        'bp_systolic', 'bp_diastolic', 'pulse', 'resp_rate', 'temperature', 'o2_sat',
        'weight_kg', 'height_cm', 'blood_sugar', 'sugar_fasting',
        'complaint', 'findings', 'advice', 'referred_to', 'follow_up_on',
        'release_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'checked_at'    => 'datetime',
        'follow_up_on'  => 'date',
        'sugar_fasting' => 'boolean',
        'temperature'   => 'float',
        'weight_kg'     => 'float',
        'height_cm'     => 'float',
        'blood_sugar'   => 'float',
    ];

    /** SQL condition for "high blood pressure" (stage 2 or worse). */
    const HIGH_BP_SQL = '(bp_systolic >= 140 OR bp_diastolic >= 90)';

    // ── Relations ──────────────────────────────────────────────────────────
    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function medicines()
    {
        return $this->hasMany(HealthCheckupMedicine::class, 'checkup_id');
    }

    /** Prescription (reseta): medicines to buy outside, in the order written. */
    public function prescriptions()
    {
        return $this->hasMany(HealthPrescription::class, 'checkup_id')->orderBy('sort_order')->orderBy('id');
    }

    public function release()
    {
        return $this->belongsTo(InventoryRelease::class, 'release_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────
    public function scopeSearch($q, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $q;
        return $q->where(function ($w) use ($term) {
            $w->where('patient_name', 'like', "%{$term}%")
              ->orWhere('complaint', 'like', "%{$term}%")
              ->orWhere('findings', 'like', "%{$term}%")
              ->orWhereHas('citizen', fn($c) => $c->searchName($term));
        });
    }

    // ── Patient ────────────────────────────────────────────────────────────
    public function isWalkIn(): bool
    {
        return !$this->citizen_id;
    }

    public function patientName(): string
    {
        return $this->citizen?->full_name ?? $this->patient_name ?? 'Unknown patient';
    }

    /** Age on the day of the check-up. */
    public function patientAge(): ?int
    {
        if ($this->citizen) {
            return $this->citizen->bday ? $this->citizen->bday->diffInYears($this->checked_at ?? now()) : null;
        }
        return $this->patient_age;
    }

    public function patientSex(): ?string
    {
        if ($this->citizen) {
            // eb_citizen.gender: 1 = male, 0 = female
            return $this->citizen->gender === null ? null : ((int) $this->citizen->gender === 1 ? 'Male' : 'Female');
        }
        return ['M' => 'Male', 'F' => 'Female'][$this->patient_sex] ?? null;
    }

    // ── Readings ───────────────────────────────────────────────────────────
    public function bloodPressure(): ?string
    {
        return $this->bp_systolic && $this->bp_diastolic ? "{$this->bp_systolic}/{$this->bp_diastolic}" : null;
    }

    public function bmi(): ?float
    {
        return $this->weight_kg && $this->height_cm ? round($this->weight_kg / (($this->height_cm / 100) ** 2), 1) : null;
    }

    /**
     * Flags: [label, tone] where tone is success | warning | danger, or null when not measured.
     * Thresholds follow common adult screening guides (AHA blood pressure, WHO BMI).
     */
    public function bpFlag(): ?array
    {
        $s = $this->bp_systolic; $d = $this->bp_diastolic;
        if (!$s || !$d) return null;
        return match (true) {
            $s >= 180 || $d >= 120 => ['Hypertensive crisis', 'danger'],
            $s >= 140 || $d >= 90  => ['High (stage 2)', 'danger'],
            $s >= 130 || $d >= 80  => ['High (stage 1)', 'warning'],
            $s >= 120              => ['Elevated', 'warning'],
            $s < 90 || $d < 60     => ['Low', 'warning'],
            default                => ['Normal', 'success'],
        };
    }

    public function bmiFlag(): ?array
    {
        $b = $this->bmi();
        if ($b === null) return null;
        return match (true) {
            $b < 18.5 => ['Underweight', 'warning'],
            $b < 25   => ['Normal', 'success'],
            $b < 30   => ['Overweight', 'warning'],
            default   => ['Obese', 'danger'],
        };
    }

    public function tempFlag(): ?array
    {
        $t = $this->temperature;
        if (!$t) return null;
        return match (true) {
            $t >= 38   => ['Fever', 'danger'],
            $t >= 37.5 => ['Slight fever', 'warning'],
            $t < 35    => ['Low', 'danger'],
            default    => ['Normal', 'success'],
        };
    }

    public function sugarFlag(): ?array
    {
        $g = $this->blood_sugar;
        if (!$g) return null;
        if ($g < 70) return ['Low', 'danger'];
        return $this->sugar_fasting
            ? match (true) { $g >= 126 => ['High', 'danger'], $g >= 100 => ['Above normal', 'warning'], default => ['Normal', 'success'] }
            : match (true) { $g >= 200 => ['High', 'danger'], $g >= 140 => ['Above normal', 'warning'], default => ['Normal', 'success'] };
    }

    public function pulseFlag(): ?array
    {
        $p = $this->pulse;
        if (!$p) return null;
        return $p < 60 ? ['Low', 'warning'] : ($p > 100 ? ['High', 'warning'] : ['Normal', 'success']);
    }

    public function o2Flag(): ?array
    {
        $o = $this->o2_sat;
        if (!$o) return null;
        return $o < 90 ? ['Low', 'danger'] : ($o < 95 ? ['Slightly low', 'warning'] : ['Normal', 'success']);
    }

    /** Readings that need attention, for list badges: [[label, tone], …] */
    public function alerts(): array
    {
        $out = [];
        foreach (['BP' => $this->bpFlag(), 'Temp' => $this->tempFlag(), 'Sugar' => $this->sugarFlag(), 'O₂' => $this->o2Flag()] as $what => $f) {
            if ($f && $f[1] !== 'success') $out[] = ["{$what}: {$f[0]}", $f[1]];
        }
        return $out;
    }

    protected static function booted(): void
    {
        // Zone-limited accounts only see records of citizens in their zones.
        static::addGlobalScope(\App\Support\ZoneAccess::SCOPE, \App\Support\ZoneAccess::byCitizenColumn('citizen_id'));
    }
}
