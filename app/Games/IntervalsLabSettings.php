<?php

namespace App\Games;

class IntervalsLabSettings extends GameFactory
{
    protected array $bonusPoints = ['timer', 'allowAccidentals', 'strictDirection'];
    protected array $categories = ['reading', 'harmony'];

    public function gameName(): string
    {
        return 'Intervals Lab';
    }

    public function gameIcon(): string
    {
        return 'flask';
    }

    public function gameDescription() : string
    {
        return 'Find and build chromatic intervals on the staff.';
    }

    public function gameTheme(): string
    {
        return 'blue';
    }

    public function categories()
    {
        return collect($this->categories);
    }

    public function gameUrl(): string
    {
        return route('games.intervals-lab.play');
    }

    protected function requiredToggleKeys(): array
    {
        return ['sound', 'showNoteNames', 'solfege', 'allowAccidentals'];
    }

    protected function defaults(): array
    {
        return [
            'timeLimit' => 20,
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
            'solfege' => false,
            'strictDirection' => false
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
