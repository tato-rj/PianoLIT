<?php

namespace App\Services;

use App\{Composer, Piece};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class FeedbackContext
{
    public function forPage(Request $request)
    {
        if (!$request->hasSession() || !$request->routeIs('webapp.*')) return null;
        $piece = $request->route('piece');
        $composer = $request->route('composer');

        return Crypt::encryptString(json_encode([
            'purpose' => 'feedback-page-v1',
            'session' => hash('sha256', $request->session()->getId()),
            'user' => optional($request->user('web'))->id,
            'page' => [
                // Search terms, campaign identifiers, tokens and fragments are unnecessary here.
                'url' => mb_strcut($request->url(), 0, 1000, 'UTF-8'),
                'route' => $request->route()->getName(),
                'piece_id' => $piece instanceof Piece ? $piece->id : null,
                'composer_id' => $composer instanceof Composer ? $composer->id : null,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function fromSubmission(Request $request, array $data)
    {
        $page = [];
        if (!empty($data['page_context'])) {
            try {
                $context = json_decode(Crypt::decryptString($data['page_context']), true);
                if (($context['purpose'] ?? null) !== 'feedback-page-v1'
                    || !hash_equals($context['session'] ?? '', hash('sha256', $request->session()->getId()))
                    || ($context['user'] ?? null) !== optional($request->user('web'))->id) {
                    throw new \UnexpectedValueException('Feedback context belongs to a different session.');
                }
                $page = $context['page'];
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages(['page_context' => 'Your session changed. Refresh the page before sending your feedback.']);
            }
        }

        $agent = substr((string) $request->userAgent(), 0, 2048);
        return array_merge($page, [
            'device_type' => $data['device_type'] ?? $this->device($agent),
            'browser' => $this->match($agent, [
                'Edge' => '/Edg(?:e|A|iOS)?\//i', 'Opera' => '/OPR\/|Opera/i',
                'Firefox' => '/Firefox\/|FxiOS\//i', 'Chrome' => '/Chrome\/|CriOS\//i',
                'Safari' => '/Safari\//i',
            ]),
            'os' => $this->match($agent, [
                'Android' => '/Android/i', 'iOS' => '/iPhone|iPad|iPod/i',
                'Windows' => '/Windows/i', 'ChromeOS' => '/CrOS/i',
                'macOS' => '/Macintosh|Mac OS X/i', 'Linux' => '/Linux/i',
            ]),
        ]);
    }

    private function match($agent, array $patterns)
    {
        foreach ($patterns as $name => $pattern) if (preg_match($pattern, $agent)) return $name;
        return 'Unknown';
    }

    private function device($agent)
    {
        if (preg_match('/iPad|Tablet/i', $agent) || (stripos($agent, 'Android') !== false && stripos($agent, 'Mobile') === false)) return 'tablet';
        return preg_match('/Mobile|iPhone|iPod/i', $agent) ? 'mobile' : 'desktop';
    }
}
