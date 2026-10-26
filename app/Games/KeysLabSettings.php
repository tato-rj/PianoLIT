<?php

namespace App\Games;

class KeysLabSettings extends GameFactory
{
    protected array $bonusPoints = ['timer', 'modes'];
    protected array $categories = ['reading'];

    public function gameName(): string
    {
        return 'Keys Lab';
    }

    public function gameIcon(): string
    {
        return 'flask';
    }

    public function gameDescription() : string
    {
        return 'Find the key signature and add accidentals on the staff.';
    }

    public function gameTheme(): string
    {
        return 'pink';
    }

    public function categories()
    {
        return collect($this->categories);
    }

    public function gameUrl(): string
    {
        return route('games.keys-lab.play');
    }

    protected function requiredToggleKeys(): array
    {
        return ['sound', 'modes'];
    }

    protected function defaults(): array
    {
        return [
            'timeLimit' => 20,
            'practiceMode' => false,
            'timer' => false,
            'numOfChallenges' => 4,
            'keyQualities' => ['major', 'minor'],
            'numberOfAccidentals' => 2,
            'modes' => false,
            'clefs' => ['treble', 'bass'],
            'sound' => true
        ];
    }

    public function options($key = null)
    {
        $options = $this->applyUserPreferences();
        $limit = filter_var($options['numberOfAccidentals'], FILTER_VALIDATE_INT);
        $options['numberOfAccidentals'] = $limit === false ? 2 : max(1, min(7, $limit));

        $array = $this->buildOptions($options);

        return $key ? $array[$key] : $array;
    }
}
