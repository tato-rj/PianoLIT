<?php

namespace App\Services\WebApp;

use App\Piece;

// Presentation follows the same saved matrix as access enforcement. Never
// advertise a feature that the destination account tier does not unlock.
class AccessPrompts
{
    private $policy;

    public function __construct(AccessPolicy $policy) { $this->policy = $policy; }

    public function prompt(string $action, string $kind = 'signup', ?Piece $piece = null)
    {
        $titles = [
            'default' => 'More music awaits', 'video' => 'Keep watching', 'audio' => 'Keep listening',
            'score' => 'Unlock the score', 'annotations' => 'Save your markings',
            'score-export' => 'Download & print', 'favorites' => 'Build your music library',
            'folders' => 'Room for more favorites', 'escore' => 'Create your practice book',
            'collections' => 'Explore every collection', 'search' => 'Find your next piece',
        ];
        $feature = ['video' => 'performance', 'score-export' => 'exports', 'favorites' => 'folder_pieces', 'escore' => 'escores'][$action] ?? $action;
        $actions = ['video' => 'watch the full video', 'audio' => 'hear the full recording', 'score' => 'read the full score',
            'annotations' => 'add and save your markings', 'score-export' => 'download and print scores',
            'favorites' => 'save your favorite pieces', 'folders' => 'organize your favorite pieces', 'escore' => 'create a practice book',
            'collections' => 'explore collections', 'search' => 'see more search results'];
        $title = $titles[$action] ?? $titles['default'];
        if ($kind === 'signup') {
            $scope = $piece ? ($piece->is_free ? 'free_pick' : 'regular') : 'account';
            $next = $this->policy->snapshot()['rules'][$scope][$feature]['registered'] ?? 'blocked';
            if (in_array($action, ['video', 'audio', 'score'], true) && $next !== 'available') {
                $title = ['video' => 'Explore more videos', 'audio' => 'Explore more recordings', 'score' => 'Explore more scores'][$action];
            }
            if ($action === 'collections') $title = 'Discover collections';
            $description = $this->policy->signupDescription($feature, $piece, $actions[$action] ?? 'explore more piano music');
        } else {
            $description = 'Go Premium to get more from every practice session.';
        }
        return compact('title', 'description', 'kind') + ['benefits' => $this->benefits($kind)];
    }

    public function premiumCopy()
    {
        $copy = [];
        foreach (['default', 'video', 'audio', 'score', 'annotations', 'score-export', 'favorites', 'folders', 'escore', 'collections', 'search'] as $action) {
            $prompt = $this->prompt($action, 'premium');
            $copy[$action] = array_intersect_key($prompt, array_flip(['title', 'description']));
        }
        return $copy;
    }

    public function benefits(string $kind)
    {
        $rules = $this->policy->snapshot()['rules'];
        $from = $kind === 'signup' ? 'guest' : 'registered';
        $to = $kind === 'signup' ? 'registered' : 'member';
        $state = function ($scope, $feature, $role) use ($rules) {
            if ($role === 'guest' && !empty(AccessPolicy::features()[$scope][$feature]['account'])) return 'blocked';
            if ($feature === 'annotations' && $rules[$scope]['score'][$role] !== 'available') return 'blocked';
            return $rules[$scope][$feature][$role];
        };
        $gain = function ($scope, $feature) use ($state, $from, $to) {
            $rank = ['blocked' => 0, 'limited' => 1, 'available' => 2];
            return $rank[$state($scope, $feature, $to)] > $rank[$state($scope, $feature, $from)];
        };
        $items = [];
        if ($gain('account', 'search')) $items['search'] = $state('account', 'search', $to) === 'available' ? 'Unlimited search results' : 'See the first 3 search results';
        $folders = $state('account', 'folders', $to);
        $pieces = $state('account', 'folder_pieces', $to);
        if (($gain('account', 'folders') || $gain('account', 'folder_pieces')) && $folders !== 'blocked' && $pieces !== 'blocked') {
            $items['favorites'] = $folders === 'available' && $pieces === 'available' ? 'Unlimited favorite folders & saved pieces'
                : ($folders === 'limited' ? 'Up to 2 favorite folders' : 'Unlimited favorite folders').($pieces === 'limited' ? ', with up to 5 pieces in each' : ' & unlimited saved pieces');
        }
        foreach (['free_pick', 'regular'] as $scope) {
            $full = []; $limited = [];
            foreach (['performance' => 'videos', 'synthesia' => 'Synthesia', 'audio' => 'audio'] as $feature => $label) {
                if (!$gain($scope, $feature)) continue;
                if ($state($scope, $feature, $to) === 'available') $full[$feature] = $label;
                else $limited[] = $label;
            }
            $join = function ($labels) {
                $last = array_pop($labels);
                return $labels ? implode(', ', $labels).' & '.$last : $last;
            };
            if ($full) {
                $all = $scope === 'regular';
                foreach (array_keys($full) as $feature) $all = $all && $state('free_pick', $feature, $to) === 'available';
                $items[$scope.'_media'] = 'Full '.$join(array_values($full)).($scope === 'free_pick' ? ' for the weekly free pick' : ($all ? ' for every piece' : ' beyond the weekly free pick'));
            } elseif ($limited) $items[$scope.'_media'] = '10-second '.$join($limited).' previews';
            $read = $state($scope, 'score', $to) === 'available';
            $mark = $state($scope, 'annotations', $to) === 'available';
            if ($read && ($gain($scope, 'score') || $gain($scope, 'annotations'))) {
                $all = $scope === 'regular' && $state('free_pick', 'score', $to) === 'available' && (!$mark || $state('free_pick', 'annotations', $to) === 'available');
                $items[$scope.'_score'] = ($mark ? 'Read & annotate ' : 'Read ').($scope === 'free_pick' ? 'the weekly free pick score' : ($all ? 'every score' : 'scores beyond the weekly free pick'));
            }
        }
        if ($gain('account', 'collections')) $items['collections'] = $state('account', 'collections', $to) === 'available' ? 'Every piece in every collection' : 'Preview 3 pieces in every collection';
        if ($gain('account', 'personalization')) $items['personalization'] = 'Recently viewed pieces & personal suggestions';
        foreach (['regular', 'free_pick'] as $scope) {
            if ($gain($scope, 'exports')) {
                $items['exports'] = $state('regular', 'exports', $to) === 'available' && $state('free_pick', 'exports', $to) === 'available'
                    ? 'Download & print public-domain scores' : ($scope === 'free_pick' ? 'Download & print the weekly free pick score' : 'Download & print scores beyond the free pick');
            }
        }
        if ($gain('account', 'escores') && ($state('regular', 'exports', $to) === 'available' || $state('free_pick', 'exports', $to) === 'available')) $items['escores'] = 'Create eScores for your practice';
        $order = $kind === 'signup'
            ? ['search', 'favorites', 'free_pick_media', 'free_pick_score', 'collections', 'personalization', 'regular_media', 'regular_score', 'exports', 'escores']
            : ['regular_media', 'regular_score', 'exports', 'favorites', 'collections', 'escores', 'search', 'personalization', 'free_pick_media', 'free_pick_score'];
        return array_values(array_slice(array_intersect_key(array_replace(array_fill_keys($order, null), $items), $items), 0, 6));
    }
}
