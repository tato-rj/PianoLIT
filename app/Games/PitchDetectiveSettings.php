<?php

namespace App\Games;

class PitchDetectiveSettings extends GameFactory
{
    protected array $bonusPoints = ['timer'];
    protected array $categories = ['ear training'];

    public function gameName(): string
    {
        return 'Pitch Detective';
    }

    public function gameIcon(): string
    {
        return 'magnifying-glass';
    }

    public function gameDescription() : string
    {
        return 'Hear an interval and write the note on the staff.';
    }

    public function gameTheme(): string
    {
        return 'green';
    }

    public function categories()
    {
        return collect($this->categories);
    }

    public function gameUrl(): string
    {
        return route('games.pitch-detective.play');
    }

    protected function requiredToggleKeys(): array
    {
        return ['sound', 'showNoteNames', 'solfege', 'allowAccidentals'];
    }

    protected function defaults(): array
    {
        return [
            'timerLimit' => 20,
            'practiceMode' => false,
            'timer' => false,
            'maxUserNotes' => 1,
            'numOfChallenges' => 4,
            'intervals' => ['M2', 'm3', 'M3', 'P5', 'P8'],
            'clefs' => ['treble', 'bass'],
            'fixedNotes' => [],
            'sound' => true,
            'showNoteNames' => false,
            'allowAccidentals' => false,
            'direction' => ['up'],
            'solfege' => false
        ];
    }

    public function options($key = null)
    {
        $options = $this->applyUserPreferences();

        $weights = $this->getAccidentalWeights()[(bool) $options['allowAccidentals']];
        $array = $this->buildOptions($options, ['accidentalWeights' => $weights]);

        return $key ? $array[$key] : $array;
    }
}
