<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relation extends Model
{
    protected $table = 'eb_relation';
    public $timestamps = false;
    protected $fillable = ['description'];
}
