<?php

namespace App\Games;

class NotePythonSettings extends GameFactory
{
    protected array $bonusPoints = ['showBombs', 'realWalls', 'speedUpEachRound'];
    protected array $categories = ['harmony'];

    public function gameName(): string
    {
        return 'Note Python';
    }

    public function gameIcon(): string
    {
        return 'signs-post';
    }

    public function gameDescription() : string
    {
        return 'Chase and collect the note by intervals.';
    }

    public function gameTheme(): string
    {
        return 'red';
    }

    public function categories()
    {
        return collect($this->categories);
    }

    public function gameUrl(): string
    {
        return route('games.note-python.play');
    }

    protected function requiredToggleKeys(): array
    {
        return ['sound', 'solfege', 'allowAccidentals', 'strictDirection', 'showBombs', 'speedUpEachRound'];
    }

    protected function defaults(): array
    {
        return [
            'bpm' => 80,
            'timeLimit' => 40,
            'practiceMode' => false,
            'timer' => false,
            'numOfChallenges' => 4,
            'intervals' => ['M2', 'm3', 'M3', 'P5', 'P8'],
            'initialNotes' => [],
            'sound' => true,
            'solfege' => false,
            'allowAccidentals' => false,
            'strictDirection' => false,
            'showBombs' => false,
            'realWalls' => false,
            'speedUpEachRound' => false,
        ];
    }

    public function options($key = null)
    {
        $options = $this->applyUserPreferences();
        $bpm = is_numeric($options['bpm'] ?? null) ? (float) $options['bpm'] : 80;
        $options['bpm'] = (int) max(50, min(160, $bpm));

        $weights = $this->getAccidentalWeights()[(bool) $options['allowAccidentals']];
        $array = $this->buildOptions($options, ['accidentalWeights' => $weights]);

        return $key ? $array[$key] : $array;
    }
}
