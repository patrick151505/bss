<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegislationEvent extends Model
{
    protected $table = 'eb_legislation_events';

    protected $fillable = ['legislation_id', 'event', 'event_date', 'remarks', 'user_id'];

    protected $casts = ['event_date' => 'date'];

    /** label, icon, colour classes for the timeline dot */
    const EVENTS = [
        'introduced'     => ['Introduced',      'mgc_file_new_line',      'bg-gray-100 dark:bg-gray-700',   'text-gray-500 dark:text-gray-300'],
        'first_reading'  => ['First reading',   'mgc_book_2_line',        'bg-primary/10',                  'text-primary'],
        'second_reading' => ['Second reading',  'mgc_book_2_line',        'bg-primary/10',                  'text-primary'],
        'third_reading'  => ['Third reading',   'mgc_book_2_line',        'bg-primary/10',                  'text-primary'],
        'approved'       => ['Approved',        'mgc_check_circle_line',  'bg-success/10',                  'text-success'],
        'vetoed'         => ['Vetoed',          'mgc_forbid_circle_line', 'bg-danger/10',                   'text-danger'],
        'posted'         => ['Posted / published', 'mgc_announcement_line', 'bg-info/10',                   'text-info'],
        'effective'      => ['Took effect',     'mgc_flag_2_line',        'bg-success/10',                  'text-success'],
        'amended'        => ['Amended',         'mgc_edit_line',          'bg-warning/10',                  'text-warning'],
        'repealed'       => ['Repealed',        'mgc_close_circle_line',  'bg-danger/10',                   'text-danger'],
        'note'           => ['Note',            'mgc_comment_line',       'bg-gray-100 dark:bg-gray-700',   'text-gray-500 dark:text-gray-300'],
    ];

    /** Events a user can add by hand from the "Add to timeline" modal. */
    const MANUAL = ['posted', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meta(): array
    {
        return self::EVENTS[$this->event] ?? self::EVENTS['note'];
    }
}
