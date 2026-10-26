<?php

namespace App\Games;

class MemoryWizardSettings extends GameFactory
{
    protected array $bonusPoints = ['hideLastNote', 'timer', 'allowAccidentals'];
    protected array $categories = ['ear training'];

    public function gameName(): string
    {
        return 'Memory Wizard';
    }

    public function gameIcon(): string
    {
        return 'hat-wizard';
    }

    public function gameDescription() : string
    {
        return 'A note reading challenge to test your memory and ear.';
    }

    public function gameTheme(): string
    {
        return 'fuchsia';
    }

    public function categories()
    {
        return collect($this->categories);
    }

    public function gameUrl(): string
    {
        return route('games.memory-wizard.play');
    }

    protected function requiredToggleKeys(): array
    {
        return ['sound', 'showNoteNames', 'solfege', 'allowAccidentals', 'showLineNames', 'hideLastNote'];
    }

    protected function defaults(): array
    {
        return [
            'timeLimit' => 15,
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
            'hideLastNote' => false,
            'solfege' => false,
            'strictDirection' => false,
            'showLineNames' => false
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
