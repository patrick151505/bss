<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitizenIdTemplate extends Model
{
    protected $table = 'eb_citizen_id_templates';

    protected $fillable = [
        'orientation_front', 'bg_front', 'html_front',
        'orientation_back',  'bg_back',  'html_back',
        'css_shared', 'js_shared',
    ];
}
