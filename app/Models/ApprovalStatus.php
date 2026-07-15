<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStatus extends Model
{
    protected $table = 'eb_approval_status';

    public $timestamps = false;

    protected $fillable = ['description'];

    public function citizens()
    {
        return $this->hasMany(Citizen::class, 'approval_status');
    }
}
