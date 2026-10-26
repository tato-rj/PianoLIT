<?php

namespace App\Games;

class ToneTrekSettings extends GameFactory
{
    protected array $bonusPoints = ['allowAccidentals', 'timer'];
    protected array $categories = ['harmony', 'ear training'];

    public function gameName(): string
    {
        return 'Tone Trek';
    }

    public function gameIcon(): string
    {
        return 'shoe-prints';
    }

    public function gameDescription() : string
    {
        return 'Follow the interval path and fill in missing notes.';
    }

    public function gameTheme(): string
    {
        return 'purple';
    }

    public function categories()
    {
        return collect($this->categories);
    }

    public function gameUrl(): string
    {
        return route('games.tone-trek.play');
    }

    protected function requiredToggleKeys(): array
    {
        return ['sound', 'solfege', 'allowAccidentals'];
    }

    protected function defaults(): array
    {
        return [
            'timeLimit' => 40,
            'practiceMode' => false,
            'timer' => false,
            'numOfChallenges' => 4,
            'intervals' => ['M2', 'm3', 'M3', 'P5', 'P8'],
            'initialNotes' => [],
            'sound' => true,
            'solfege' => false,
            'allowAccidentals' => false
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
