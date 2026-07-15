<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'eb_tags';

    protected $fillable = ['name', 'color', 'description'];

    public function citizens()
    {
        return $this->belongsToMany(Citizen::class, 'eb_citizen_tag', 'tag_id', 'citizen_id');
    }
}
