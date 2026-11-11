<?php

namespace App\Services\CrashCourses;

use App\CrashCourse\CrashCourseLesson;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\ValidationException;

/** Web-only, versioned email content. Never add this state to shared lesson JSON. */
class EmailDesign
{
    public const TYPES = ['text' => 'Text', 'hero' => 'Hero', 'image' => 'Image', 'button' => 'Button', 'panel' => 'Panel', 'columns' => 'Two columns', 'divider' => 'Divider', 'spacer' => 'Space'];

    public function saved(CrashCourseLesson $lesson): ?object
    {
        return $lesson->exists ? DB::table('crash_course_email_designs')->where('lesson_id', $lesson->id)->first() : null;
    }

    public function initial(?CrashCourseLesson $lesson = null): array
    {
        return ['version' => 1, 'settings' => ['background' => '#ffffff', 'background_image' => '', 'color' => '#242933', 'font' => 'sans', 'preheader' => ''],
            'blocks' => $lesson && $lesson->body ? [['id' => 'legacy-text', 'type' => 'text', 'content' => $lesson->body, 'padding' => 24]] : []];
    }

    public function decode($json): array
    {
        if (!is_string($json) || strlen($json) > 200000 || !is_array($design = json_decode($json, true))) {
            throw ValidationException::withMessages(['email_design' => 'The email design could not be read. Keep this page open and try again.']);
        }
        return $this->validate($design);
    }

    public function validate(array $design): array
    {
        $color = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $url = function ($attribute, $value, $fail) {
            if (!$this->safeUrl($value, substr($attribute, -3) === 'url')) $fail('Use a full HTTP or HTTPS address'.(substr($attribute, -3) === 'url' ? ', or a mailto link.' : '.'));
        };
        $rules = [
            'version' => 'required|integer|in:1', 'settings' => 'required|array', 'blocks' => 'required|array|min:1|max:40',
            'settings.background' => $color, 'settings.color' => $color,
            'settings.background_image' => ['nullable', 'string', 'max:2048', $url],
            'settings.preheader' => 'nullable|string|max:180', 'settings.font' => 'nullable|in:sans,serif',
            'blocks.*.id' => 'required|string|regex:/^[a-zA-Z0-9_-]{1,80}$/|distinct',
            'blocks.*.type' => 'required|in:'.implode(',', array_keys(self::TYPES)),
        ];
        foreach (['content', 'content_right'] as $key) $rules['blocks.*.'.$key] = 'nullable|string|max:40000';
        foreach (['title', 'alt', 'label'] as $key) $rules['blocks.*.'.$key] = 'nullable|string|max:300';
        foreach (['image', 'background_image', 'url'] as $key) $rules['blocks.*.'.$key] = ['nullable', 'string', 'max:2048', $url];
        foreach (['background', 'color', 'button_color'] as $key) $rules['blocks.*.'.$key] = $color;
        foreach (['padding' => 48, 'gap' => 64, 'radius' => 32, 'height' => 120, 'image_width' => 504] as $key => $max) $rules['blocks.*.'.$key] = 'nullable|integer|min:'.($key === 'image_width' ? 1 : 0).'|max:'.$max;
        $rules['blocks.*.align'] = 'nullable|in:left,center,right';
        $validator = Validator::make($design, $rules);
        if ($validator->fails()) {
            throw ValidationException::withMessages(['email_design' => $validator->errors()->first()]);
        }
        $settings = $design['settings'];
        $clean = ['version' => 1, 'settings' => [
            'background' => $settings['background'] ?? '#ffffff', 'color' => $settings['color'] ?? '#242933',
            'background_image' => $settings['background_image'] ?? '', 'preheader' => $settings['preheader'] ?? '', 'font' => $settings['font'] ?? 'sans',
        ], 'blocks' => []];
        foreach ($design['blocks'] as $block) {
            $clean['blocks'][] = [
                'id' => $block['id'], 'type' => $block['type'],
                'content' => $this->richText($block['content'] ?? ''), 'content_right' => $this->richText($block['content_right'] ?? ''),
                'title' => $block['title'] ?? '', 'alt' => $block['alt'] ?? '', 'label' => $block['label'] ?? '',
                'image' => $block['image'] ?? '', 'background_image' => $block['background_image'] ?? '', 'url' => $block['url'] ?? '',
                'background' => $block['background'] ?? ($block['type'] === 'panel' ? '#eef3ff' : $clean['settings']['background']),
                'color' => $block['color'] ?? $clean['settings']['color'], 'button_color' => $block['button_color'] ?? '#0055fe',
                'padding' => $block['padding'] ?? 24, 'gap' => $block['gap'] ?? 16, 'radius' => $block['radius'] ?? 0,
                'height' => $block['height'] ?? 24, 'image_width' => $block['image_width'] ?? 504, 'align' => $block['align'] ?? 'left',
            ];
        }
        if (strlen($this->html($clean)) > 60000) throw ValidationException::withMessages(['email_design' => 'This lesson is too large for one email. Shorten the text or remove some blocks.']);
        return $clean;
    }

    public function safeUrl($url, bool $link = false): bool
    {
        if (!$url) return true;
        if (!is_string($url) || preg_match('/[\x00-\x20<>"\'\\\\]/', $url)) return false;
        if ($link && preg_match('/^mailto:[^?]+$/i', $url)) return filter_var(substr($url, 7), FILTER_VALIDATE_EMAIL) !== false;
        $parts = parse_url($url);
        return $parts && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) && filter_var($url, FILTER_VALIDATE_URL);
    }

    public function richText(string $html): string
    {
        // Use the installed HTML Purifier, with a bounded email-only vocabulary.
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p[style],div[style],span[style],h1[style],h2[style],h3[style],h4[style],h5,h6,strong,b,em,i,u,s,br,ul,ol,li,blockquote,a[href|title],img[src|alt|width|height],table[width],thead,tbody,tr,td[colspan|rowspan],th');
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align', 'font-size', 'font-family', 'font-weight', 'font-style', 'text-decoration', 'padding-left']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $html = (new \HTMLPurifier($config))->purify($html);
        // Relative URLs and tracking credentials cannot become working email destinations.
        return preg_replace_callback('/\s(href|src)="([^"]*)"/i', function ($match) {
            return $this->safeUrl(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'), strtolower($match[1]) === 'href') ? $match[0] : '';
        }, $html);
    }

    public function html(array $design): string
    {
        return view('emails.courses.content', compact('design'))->render();
    }

    public function personalize(array $design, $subscription): array
    {
        $name = $subscription ? (string) $subscription->first_name : 'Joe';
        foreach ($design['blocks'] as &$block) {
            foreach (['content', 'content_right'] as $field) $block[$field] = str_replace('[first_name]', e($name), $block[$field] ?? '');
            foreach (['title', 'alt', 'label'] as $field) $block[$field] = str_replace('[first_name]', $name, $block[$field] ?? '');
        }
        $design['settings']['preheader'] = str_replace('[first_name]', $name, $design['settings']['preheader'] ?? '');
        return $design;
    }

    public function text(array $design): string
    {
        $parts = [];
        foreach ($design['blocks'] as $block) {
            foreach (['title', 'content', 'content_right', 'alt'] as $field) {
                $html = $block[$field] ?? '';
                $html = preg_replace_callback('/<a\b[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', function ($m) { return strip_tags($m[2]).' ('.$m[1].')'; }, $html);
                $html = preg_replace('/<(br\b[^>]*|\/p|\/div|\/li|\/h[1-6])>/i', "\n", $html);
                if ($text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'))) $parts[] = $text;
            }
            if (!empty($block['url'])) $parts[] = ($block['label'] ?: 'Open link').': '.$block['url'];
        }
        return implode("\n\n", $parts);
    }
}
