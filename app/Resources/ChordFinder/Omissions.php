<?php

namespace App\Resources\ChordFinder;

/** Describe absent chord tones without adding them to the played notes or intervals. */
class Omissions
{
    public static function degrees(array $intervals): array
    {
        $degrees = [];
        foreach ($intervals as $interval) {
            if (isset($interval['interval'])) {
                $degrees[($interval['interval'] - 1) % 7 + 1] = $interval['type'];
            }
        }
        return $degrees;
    }

    public static function seventhShell(array $intervals): bool
    {
        $degrees = self::degrees($intervals);
        return count($degrees) === 2 && ($degrees[5] ?? null) === 'perfect'
            && in_array($degrees[7] ?? null, ['minor', 'major'], true);
    }

    public static function qualities(array $intervals): array
    {
        if (!self::seventhShell($intervals)) return [null];

        // Both thirds fit an absent third. Order the conventional minor-seventh
        // reading first for a minor seventh, without claiming greater certainty.
        return self::degrees($intervals)[7] === 'minor' ? ['minor', 'major'] : ['major', 'minor'];
    }

    public static function describe(array $inversion, array $label): array
    {
        $degrees = self::degrees($inversion['intervals']);
        $missing = [];
        if (self::seventhShell($inversion['intervals'])) {
            $missing[] = ['degree' => 3, 'note' => self::note($inversion['chord'][0], 3, $label['type'] === 'minor' ? 3 : 4)];
        }
        if (!isset($degrees[5])) {
            $missing[] = ['degree' => 5, 'note' => self::note($inversion['chord'][0], 5, 7)];
        }
        return $missing;
    }

    private static function note(string $root, int $degree, int $semitones): string
    {
        $letters = 'cdefgab';
        $naturals = ['c' => 0, 'd' => 2, 'e' => 4, 'f' => 5, 'g' => 7, 'a' => 9, 'b' => 11];
        $letter = $letters[(strpos($letters, $root[0]) + $degree - 1) % 7];
        $pitch = $naturals[$root[0]] + substr_count($root, '+') - substr_count($root, '-');
        $accidental = (($pitch + $semitones - $naturals[$letter] + 18) % 12) - 6;
        return strtoupper($letter).str_repeat($accidental < 0 ? 'b' : '#', abs($accidental));
    }
}
