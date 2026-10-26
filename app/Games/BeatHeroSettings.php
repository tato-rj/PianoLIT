<?php

namespace App\Games;

class BeatHeroSettings extends GameFactory
{
    private const FIGURE_CHOICES = [
        'half' => 'Half note',
        'quarter' => 'Quarter note',
        'two-eighths' => 'Two eighth notes',
        'triplets' => 'Eighth-note triplets',
        'eighth-two-sixteenths' => 'Eighth note, two sixteenth notes',
        'sixteenth-eighth-sixteenth' => 'Sixteenth note, eighth note, sixteenth note',
        'two-sixteenths-eighth' => 'Two sixteenth notes, eighth note',
        'four-sixteenths' => 'Four sixteenth notes',
        'dotted-eighth-sixteenth' => 'Dotted eighth note, sixteenth note',
        'sixteenth-dotted-eighth' => 'Sixteenth note, dotted eighth note',
    ];

    private const DEFAULT_FIGURES = [
        'quarter',
        'two-eighths',
        'four-sixteenths',
        'eighth-two-sixteenths',
    ];

    protected array $bonusPoints = [];
    protected array $categories = ['rhythm', 'reading'];

    public function public()
    {
        return true;
    }

    public function gameName(): string
    {
        return 'Beat Hero';
    }

    public function gameIcon(): string
    {
        return 'drum';
    }

    public function gameDescription() : string
    {
        return 'Listen to a sequence of rhythm cards, then find them in the right order.';
    }

    public function gameTheme(): string
    {
        return 'yellow';
    }

    public function categories()
    {
        return collect($this->categories);
    }

    public function gameUrl(): string
    {
        return route('games.beat-hero.play');
    }

    protected function requiredToggleKeys(): array
    {
        return ['practiceMode'];
    }

    protected function defaults(): array
    {
        return [
            'practiceMode' => false,
            'numOfChallenges' => 4,
            'numOfCards' => 2,
            'bpm' => 80,
            'sound' => true,
            'figures' => self::DEFAULT_FIGURES,
        ];
    }

    public function figureChoices(): array
    {
        return self::FIGURE_CHOICES;
    }

    public function options($key = null)
    {
        $options = $this->applyUserPreferences();
        $options['figures'] = $this->normalizeFigures($options['figures'] ?? []);
        $count = $options['numOfCards'] ?? 2;
        $options['numOfCards'] = is_numeric($count) ? (int) max(2, min(6, (float) $count)) : 2;
        $array = $this->buildOptions($options);

        return $key ? $array[$key] : $array;
    }

    private function normalizeFigures($figures): array
    {
        $figures = is_array($figures) ? $figures : [];
        $figures = array_values(array_unique(array_filter(
            $figures,
            fn ($figure) => is_string($figure) && array_key_exists($figure, self::FIGURE_CHOICES)
        )));

        foreach (self::DEFAULT_FIGURES as $defaultFigure) {
            if (count($figures) >= 2) break;
            if (!in_array($defaultFigure, $figures, true)) {
                $figures[] = $defaultFigure;
            }
        }

        return $figures;
    }
}
