<?php

namespace App\Games;

class NoteMatchSettings extends GameFactory
{
    protected array $bonusPoints = ['timer'];
    protected array $categories = ['reading'];

    public function gameName(): string
    {
        return 'Note Match';
    }

    public function gameIcon(): string
    {
        return 'graduation-cap';
    }

    public function gameDescription() : string
    {
        return 'Read the note on the staff and play it out loud.';
    }

    public function gameTheme(): string
    {
        return 'teal';
    }

    public function categories()
    {
        return collect($this->categories);
    }

    public function gameUrl(): string
    {
        return route('games.note-match.play');
    }

    protected function requiredToggleKeys(): array
    {
        return ['sound', 'showNoteNames', 'solfege', 'allowAccidentals', 'showLineNames'];
    }

    protected function defaults(): array
    {
        return [
            'timeLimit' => 15,
            'practiceMode' => false,
            'timer' => false,
            'maxUserNotes' => 0,
            'numOfChallenges' => 4,
            'clefs' => ['treble', 'bass'],
            'sound' => true,
            'showNoteNames' => false,
            'allowAccidentals' => false,
            'solfege' => false,
            'showLineNames' => false,
            'requirePlayedNote' => true,
            'instructionsAfterUserNotes' => 1,
            'checkAfterUserNotes' => 0,
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
