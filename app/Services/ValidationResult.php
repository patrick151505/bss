<?php

namespace App\Services;

class ValidationResult
{
    public readonly bool $passes;

    public function __construct(
        public readonly string $rule,
        public readonly string $label,
        public readonly float  $threshold_pct,
        public readonly float  $required,
        public readonly float  $actual,
        public readonly string $basis,
        public readonly string $status,  // 'pass' | 'fail' | 'warning' | 'no_data'
    ) {
        $this->passes = ($status === 'pass');
    }

    public static function noData(string $rule, string $label, float $threshold_pct, string $basis): self
    {
        return new self(
            rule: $rule,
            label: $label,
            threshold_pct: $threshold_pct,
            required: 0,
            actual: 0,
            basis: $basis,
            status: 'no_data',
        );
    }

    public function toArray(): array
    {
        return [
            'rule'          => $this->rule,
            'label'         => $this->label,
            'threshold_pct' => $this->threshold_pct,
            'required'      => $this->required,
            'actual'        => $this->actual,
            'basis'         => $this->basis,
            'status'        => $this->status,
            'passes'        => $this->passes,
        ];
    }
}
