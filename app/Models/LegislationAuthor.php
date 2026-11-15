<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegislationAuthor extends Model
{
    protected $table = 'eb_legislation_authors';

    protected $fillable = ['legislation_id', 'official_id', 'name', 'role'];

    public function official()
    {
        return $this->belongsTo(Official::class);
    }

    public function roleLabel(): string
    {
        return Legislation::AUTHOR_ROLES[$this->role] ?? ucfirst($this->role);
    }
}
