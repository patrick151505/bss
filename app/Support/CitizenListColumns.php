<?php

namespace App\Support;

use App\Models\Citizen;

/**
 * Columns of the Citizens list that the Columns menu can show or hide.
 * STANDARD are shown unless the user hides them; MORE are extra details
 * that stay hidden until ticked. Name, # and actions are always shown.
 */
class CitizenListColumns
{
    public const STANDARD = [
        'household'  => 'Household',
        'age'        => 'Age',
        'gender'     => 'Gender',
        'address'    => 'Address / Zone',
        'civil'      => 'Civil Status',
        'tags'       => 'Tags',
        'flags'      => 'Flags',
        'status'     => 'Status',
        'registered' => 'Registered',
        'updated'    => 'Last Updated',
    ];

    public const MORE = [
        'contact'     => 'Contact No.',
        'email'       => 'Email',
        'bplace'      => 'Place of Birth',
        'citizenship' => 'Citizenship',
        'occupation'  => 'Occupation',
        'precinct'    => 'Precinct No.',
        'year_stay'   => 'Resident Since',
        'owner'       => 'House Ownership',
        'id_released' => 'Barangay ID',
        'verified'    => 'Verified',
        'emergency'   => 'Emergency Contact',
    ];

    /** Plain-text value of an extra column for one citizen ('' = none). */
    public static function value(string $key, Citizen $c): string
    {
        return trim((string) match ($key) {
            'contact'     => $c->contact,
            'email'       => $c->email,
            'bplace'      => $c->bplace,
            'citizenship' => $c->citizenship,
            'occupation'  => $c->occupation,
            'precinct'    => $c->pricinct_no,
            'year_stay'   => $c->year_stay?->format('M Y'),
            'owner'       => (int) $c->owner_status === 1 ? 'Owner'
                             : ('Renter / Others' . ($c->house_owner_name ? ' (owner: ' . $c->house_owner_name . ')' : '')),
            'id_released' => $c->is_id_release ? 'Released' : 'Not yet',
            'verified'    => $c->is_verify ? 'Yes' : 'No',
            'emergency'   => collect([$c->ic_fullname, $c->ic_relationship ? '(' . $c->ic_relationship . ')' : null, $c->ic_contact])
                                ->filter()->implode(' '),
            default       => '',
        });
    }
}
