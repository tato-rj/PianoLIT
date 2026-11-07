<?php

namespace App\Resources\ChordFinder;

class KeySignature
{
    public static function choices(): array
    {
        return [
            '' => 'Not specified',
            '0' => 'C major / A minor',
            '1' => 'G major / E minor', '2' => 'D major / B minor',
            '3' => 'A major / F# minor', '4' => 'E major / C# minor',
            '5' => 'B major / G# minor', '6' => 'F# major / D# minor',
            '7' => 'C# major / A# minor',
            '-1' => 'F major / D minor', '-2' => 'Bb major / G minor',
            '-3' => 'Eb major / C minor', '-4' => 'Ab major / F minor',
            '-5' => 'Db major / Bb minor', '-6' => 'Gb major / Eb minor',
            '-7' => 'Cb major / Ab minor',
        ];
    }

    public static function contains(int $signature, string $note): bool
    {
        $altered = substr($signature < 0 ? 'BEADGCF' : 'FCGDAEB', 0, abs($signature));
        $expected = $note[0].(strpos($altered, $note[0]) !== false ? ($signature < 0 ? 'b' : '#') : '');
        return $note === $expected;
    }
}
