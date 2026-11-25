<?php

namespace App\Support;

use App\Models\Citizen;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Finds citizens that may be the same person as the one being added or edited.
 *
 * Looks at EVERY record — archived citizens, walk-ins and other zones — not
 * just the ones the current account can see, so a person can't be added twice
 * just because their first record is hidden.
 *
 *  - exact():   same first name + last name + birthday (ignoring capitals,
 *               extra spaces, dots and ñ/n) — plus middle name when turned on
 *               in Settings → Citizen Fields. Blocks the save.
 *  - similar(): same birthday with a close name (typo, "Ma." / "Maria",
 *               "Juan" / "Juan Carlos"), or the same name with a different
 *               birthday. Staff must confirm before saving.
 */
class CitizenDuplicates
{
    /** Lower-case, single spaces, no dots/commas, ñ → n. */
    public static function norm(?string $s): string
    {
        $s = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $s)));
        $s = str_replace(['.', ','], '', strtr($s, ['ñ' => 'n']));
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    public static function exact(?string $fname, ?string $lname, $bday, ?int $exceptId = null, ?string $mname = null): ?Citizen
    {
        $date = self::date($bday);
        $f = self::norm($fname);
        $l = self::norm($lname);
        if (!$date || $f === '' || $l === '') {
            return null;
        }

        return self::sameBirthday($date, $exceptId)
            ->first(fn ($c) => self::norm($c->fname) === $f && self::norm($c->lname) === $l && self::middleMatches($c->mname, $mname));
    }

    /** @return Collection<Citizen> each with a ->dup_reason */
    public static function similar(?string $fname, ?string $lname, $bday, ?int $exceptId = null, ?string $mname = null): Collection
    {
        $date = self::date($bday);
        $f = self::norm($fname);
        $l = self::norm($lname);
        if ($f === '' || $l === '') {
            return collect();
        }

        $out = collect();

        if ($date) {
            foreach (self::sameBirthday($date, $exceptId) as $c) {
                $cf = self::norm($c->fname);
                $cl = self::norm($c->lname);
                if ($cf === $f && $cl === $l) {
                    if (self::middleMatches($c->mname, $mname)) {
                        continue;                   // exact match — handled by exact()
                    }
                    $c->dup_reason = 'Same name and birthday, different middle name';
                    $out->push($c);
                    continue;
                }
                if (self::close($cl, $l) && self::firstNameClose($cf, $f)) {
                    $c->dup_reason = 'Same birthday, similar name';
                    $out->push($c);
                }
            }
        }

        // Same name, different birthday — a mistyped birthday, or a namesake.
        self::base()
            ->where('fname', trim($fname))
            ->where('lname', trim($lname))
            ->when($date, fn ($q) => $q->where('bday', '!=', $date))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->limit(5)
            ->get()
            ->each(function ($c) use ($out) {
                $c->dup_reason = 'Same name, different birthday';
                $out->push($c);
            });

        return $out->unique('id')->take(6)->values();
    }

    /**
     * Everything a save needs: `block` is the message that stops it (exact
     * match), `similar` the possible matches staff must confirm. A walk-in
     * with the same name and birthday is only a warning — registering a
     * walk-in as a resident is a normal thing to do.
     */
    public static function check(?string $fname, ?string $lname, $bday, ?int $exceptId = null, ?string $mname = null): array
    {
        $exact = self::exact($fname, $lname, $bday, $exceptId, $mname);
        if ($exact && self::status($exact) !== 'walkin') {
            return ['block' => self::blockMessage($exact), 'exact' => $exact, 'similar' => collect()];
        }

        $similar = self::similar($fname, $lname, $bday, $exceptId, $mname);
        if ($exact) {
            $exact->dup_reason = 'Same name and birthday — saved as a walk-in';
            $similar->prepend($exact);
        }

        return ['block' => null, 'exact' => $exact, 'similar' => $similar->unique('id')->values()];
    }

    /** What kind of record this is, for the messages. */
    public static function status(Citizen $c): string
    {
        if (!$c->is_active) {
            return 'archived';
        }
        if ($c->is_walkin) {
            return 'walkin';
        }
        return ZoneAccess::allows($c->address) ? 'active' : 'other_zone';
    }

    /** Plain data for the form's warning panel. */
    public static function describe(Citizen $c): array
    {
        $status = self::status($c);
        $name   = trim($c->lname . ', ' . $c->fname . ' ' . ($c->mname ? mb_substr($c->mname, 0, 1) . '.' : '') . ' ' . $c->suffix);

        return [
            'id'     => $c->id,
            'code'   => Setting::instance()->formatCitizenId($c->id),
            'name'   => preg_replace('/\s+/', ' ', $name),
            'bday'   => $c->bday ? Carbon::parse($c->bday)->format('M d, Y') : '—',
            'status' => $status,
            'label'  => [
                'active'     => 'In the citizens list',
                'archived'   => 'Archived',
                'walkin'     => 'Walk-in (document request only)',
                'other_zone' => 'Another zone',
            ][$status],
            'reason' => $c->dup_reason ?? 'Same name and birthday',
            'url'    => match ($status) {
                'active'   => route('citizens.show', $c->id),
                'archived' => route('citizens.inactive', ['search' => $c->lname . ' ' . $c->fname]),
                default    => null,
            },
        ];
    }

    /** The message that blocks the save for an exact match. */
    public static function blockMessage(Citizen $c): string
    {
        $d    = self::describe($c);
        $who  = "{$d['name']} ({$d['code']}), born {$d['bday']}";

        return match ($d['status']) {
            'archived'   => "This person is already recorded but archived: {$who}. Restore them from the Archive instead of adding them again.",
            'other_zone' => "This person is already registered in a zone your account cannot see: {$who}. Ask an administrator to check.",
            default      => "This person is already in the citizens list: {$who}.",
        };
    }

    // ── internals ─────────────────────────────────────────────────────

    /**
     * Settings → Citizen Fields can make the middle name part of the match.
     * A blank middle name on either side still counts as a match — it may
     * just not have been typed.
     */
    private static function middleMatches(?string $existing, ?string $typed): bool
    {
        if (!CitizenFieldRules::matchMiddleName()) {
            return true;
        }
        $a = self::norm($existing);
        $b = self::norm($typed);
        return $a === '' || $b === '' || $a === $b || $a === mb_substr($b, 0, 1) || $b === mb_substr($a, 0, 1);
    }

    private static function base()
    {
        return Citizen::withoutGlobalScopes()
            ->select('id', 'fname', 'mname', 'lname', 'suffix', 'bday', 'address', 'is_active', 'is_walkin');
    }

    private static function sameBirthday(string $date, ?int $exceptId): Collection
    {
        [, $m, $d] = array_map('intval', explode('-', $date));

        return self::base()
            ->where('bday_month', $m)->where('bday_day', $d)   // uses the birthday index
            ->where('bday', $date)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get();
    }

    private static function date($bday): ?string
    {
        if (!$bday) {
            return null;
        }
        try {
            return Carbon::parse($bday)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Equal, or a small typo (1 letter for short names, 2 for longer ones). */
    private static function close(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }
        $max = max(strlen($a), strlen($b));
        return $max > 0 && levenshtein($a, $b) <= ($max >= 6 ? 2 : 1);
    }

    /** "Juan" ~ "Juan Carlos", "Ma" ~ "Maria", "Jhon" ~ "John". */
    private static function firstNameClose(string $a, string $b): bool
    {
        if (self::close($a, $b)) {
            return true;
        }
        $ta = explode(' ', $a)[0];
        $tb = explode(' ', $b)[0];
        if ($ta === '' || $tb === '') {
            return false;
        }
        if (self::close($ta, $tb)) {
            return true;
        }
        $short = strlen($ta) <= strlen($tb) ? $ta : $tb;
        $long  = $short === $ta ? $tb : $ta;
        return strlen($short) >= 2 && str_starts_with($long, $short);
    }
}
