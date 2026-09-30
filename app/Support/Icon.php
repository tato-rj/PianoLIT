<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Icon
{
    protected static $icons;
    protected static $aliases;

    protected static function solidColor($value): ?string
    {
        if (!is_string($value)) return null;
        $color = trim($value);
        if (preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $color)) return $color;
        if (!preg_match('/^(rgb|rgba)\(([^()]*)\)$/i', $color, $matches)) return null;

        $parts = array_map('trim', explode(',', $matches[2]));
        if (count($parts) !== (strtolower($matches[1]) === 'rgb' ? 3 : 4)) return null;
        foreach ($parts as $index => $part) {
            if (!preg_match('/^(?:\d+(?:\.\d+)?|\.\d+)%?$/', $part)) return null;
            $percent = substr($part, -1) === '%';
            $limit = $index === 3 ? ($percent ? 100 : 1) : ($percent ? 100 : 255);
            if ((float) $part > $limit) return null;
        }
        return $color;
    }

    public static function resolve(string $name): string
    {
        if (static::$aliases === null) {
            static::$aliases = json_decode(file_get_contents(resource_path('icons/aliases.json')), true);
        }
        // Legacy model/API values remain unchanged; translate only at the view boundary.
        if (preg_match('/\bfa-([a-z0-9-]+)/', $name, $matches)) {
            $name = strpos($name, 'fab ') !== false ? 'brand-'.$matches[1] : $matches[1];
        }
        return static::$aliases[$name] ?? $name;
    }

    public static function render(string $name, array $options = []): HtmlString
    {
        $name = static::resolve($name);
        if (static::$icons === null) {
            static::$icons = json_decode(file_get_contents(resource_path('icons/lucide.json')), true);
        }
        $brand = strpos($name, 'brand-') === 0 && isset(static::$aliases[$name]);
        if (!$brand && !isset(static::$icons[$name])) {
            $name = 'circle-help';
        }

        $classes = [$brand ? 'app-brand-icon fab fa-'.substr($name, 6) : 'app-icon icon-'.$name];
        $classes[] = 'mr-'.($options['mr'] ?? 2);
        foreach (['ml' => 'ml-', 'color' => 'text-', 'size' => 'icon-size-', 'weight' => 'icon-weight-'] as $key => $prefix) {
            if (isset($options[$key])) $classes[] = $prefix.$options[$key];
        }
        if (!empty($options['filled']) || !empty($options['solid'])) $classes[] = 'icon-filled';
        if (!empty($options['classes'])) $classes[] = $options['classes'];
        $attributes = ['class' => implode(' ', $classes)];
        foreach (['title', 'name'] as $key) {
            if (isset($options[$key])) $attributes[$key] = $options[$key];
        }
        $style = isset($options['if']) && !$options['if'] ? 'display: none;' : '';
        $style .= $options['styles'] ?? '';
        if ($style !== '') $attributes['style'] = $style;
        $label = $options['label'] ?? $options['title'] ?? null;
        if ($label) {
            $attributes['role'] = 'img';
            $attributes['aria-label'] = $label;
        } else {
            $attributes['aria-hidden'] = 'true';
        }
        foreach ($options['attributes'] ?? [] as $key => $value) {
            if (preg_match('/^(?:data-[a-z0-9-]+|aria-[a-z0-9-]+|id|role|tabindex)$/', $key)) {
                $attributes[$key] = $value;
            }
        }
        $html = '<i';
        foreach ($attributes as $key => $value) {
            $html .= ' '.$key.'="'.e($value).'"';
        }
        $html .= '>';
        if (!$brand) {
            $solid = $options['solid'] ?? false;
            $solidColor = static::solidColor($solid);
            $fill = $solidColor ?? ($solid ? 'currentColor' : 'none');
            // The icon-filled CSS class supports toggles; an inline fill lets an
            // explicit solid color override it without changing the outline.
            $fillStyle = $solidColor !== null ? ' style="fill: '.e($solidColor).'"' : '';
            $html .= '<svg data-lucide-name="'.$name.'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="'.e($fill).'"'.$fillStyle.' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.static::$icons[$name].'</svg>';
        }
        return new HtmlString($html.'</i>');
    }
}
