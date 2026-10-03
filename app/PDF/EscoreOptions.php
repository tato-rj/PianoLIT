<?php

namespace App\PDF;

class EscoreOptions
{
    public const DEFAULT_COLOR = '#00a2ff';
    public const IMAGE_COVER_DEFAULT_COLOR = '#ebebeb';

    public static function rules()
    {
        return [
            'title' => 'required|string|max:160',
            'subtitle' => 'nullable|string|max:160',
            'comment' => 'nullable|string|max:600',
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'bottom_text' => 'nullable|string|max:160',
            'edition_notes' => 'nullable|string|max:4000',
            'piece_ids' => 'sometimes|required|array|min:1|max:1000',
            'piece_ids.*' => 'required|integer|distinct',
            'page_size' => 'sometimes|in:letter,a4',
            'cover_style' => 'sometimes|in:reference,modern',
            'page_numbers' => 'sometimes|boolean',
            'composer_names' => 'sometimes|boolean',
            'include_edition' => 'sometimes|boolean',
            'blank_pages' => 'sometimes|boolean',
            'title_page' => 'sometimes|boolean',
            'preview' => 'sometimes|boolean',
        ];
    }

    public static function textColor($color)
    {
        $channels = array_map(function ($offset) use ($color) {
            $value = hexdec(substr($color, $offset, 2)) / 255;
            return $value <= 0.04045 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4);
        }, [1, 3, 5]);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2] > 0.179
            ? '#000000' : '#ffffff';
    }

    public static function validate($request)
    {
        $options = $request->validate(self::rules());
        // Laravel converts submitted empty strings to null. Preserve deliberate
        // blank cover fields while allowing omitted fields to use defaults.
        foreach (['subtitle', 'comment', 'bottom_text', 'edition_notes'] as $field) {
            if (array_key_exists($field, $options)) $options[$field] = $options[$field] ?? '';
        }
        return $options;
    }

    public static function selectPieces($pieces, array $options)
    {
        if (!isset($options['piece_ids'])) return $pieces;
        $eligible = $pieces->keyBy('id');
        $selected = collect($options['piece_ids'])->map(function ($id) use ($eligible) { return $eligible->get($id); });
        if ($selected->contains(null)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['piece_ids' => 'Choose only available scores from this folder or collection.']);
        }
        return $selected;
    }
}
